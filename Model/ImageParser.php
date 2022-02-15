<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model;

use Hryvinskyi\ResponsiveImages\Module\ImageInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\PictureInterfaceFactory;
use Hryvinskyi\SeoImageOptimizerApi\Model\Convertor\ConvertorListing;
use Hryvinskyi\SeoImageOptimizerApi\Model\ImageParserInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ImageParser implements ImageParserInterface
{
    private ConvertorListing $convertorListing;
    private PictureInterfaceFactory $pictureFactory;
    private StoreManagerInterface $storeManager;
    private ImageInterfaceFactory $imageFactory;
    private LoggerInterface $logger;
    private string $imgRegex;

    /**
     * @param ConvertorListing $convertorListing
     * @param PictureInterfaceFactory $pictureFactory
     * @param StoreManagerInterface $storeManager
     * @param ImageInterfaceFactory $imageFactory
     * @param LoggerInterface $logger
     * @param string $imgRegex
     */
    public function __construct(
        ConvertorListing $convertorListing,
        PictureInterfaceFactory $pictureFactory,
        StoreManagerInterface $storeManager,
        ImageInterfaceFactory $imageFactory,
        LoggerInterface $logger,
        string $imgRegex = '/<img([^<]+\s|\s)src=(\"|' . "\')([^<]+?\.(png|jpg|jpeg))[^<]+>(?!(<\/pic|\s*<\/pic))/mi"
    ) {
        $this->convertorListing = $convertorListing;
        $this->pictureFactory = $pictureFactory;
        $this->storeManager = $storeManager;
        $this->imageFactory = $imageFactory;
        $this->logger = $logger;
        $this->imgRegex = $imgRegex;
    }

    public function execute(string $content): string
    {
        if (preg_match_all($this->imgRegex, $content, $images, PREG_OFFSET_CAPTURE) === false) {
            return $content;
        }

        $accumulatedChange = 0;
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $staticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $mediaUrlWithoutBaseUrl = str_replace($baseUrl, '', $mediaUrl);
        $excludeImageAttributes = $this->getExcludeImageAttributes();
        foreach ($images[0] as $index => $image) {
            $offset = $image[1] + $accumulatedChange;
            $htmlTag = $images[0][$index][0];
            $imageUrl = $images[3][$index][0];

            /**
             * Skip when image is not from same server
             */
            if (strpos($imageUrl, $mediaUrl) === false &&
                strpos($imageUrl, $mediaUrlWithoutBaseUrl) === false &&
                strpos($imageUrl, $staticUrl) === false
            ) {
                continue;
            }

            /**
             * Skip when image contains an excluded attribute
             */
            $isValidRegex = false;
            try {
                preg_match($excludeImageAttributes, '');
                $isValidRegex = true;
            } catch (\Throwable $e) {
                $this->logger->info("Conversion Blacklist Configuration is invalid:" . $excludeImageAttributes);
                $this->logger->info("Detail: " . $e->getMessage());
            }

            if ($isValidRegex && preg_match_all($excludeImageAttributes, $htmlTag)) {
                continue;
            }

            $picture = $this->pictureFactory->create();
            $picture->setImage($this->imageFactory->create()->setRenderedTag($htmlTag));

            foreach ($this->convertorListing->getConvertors() as $convertor) {
                $convertor->execute($htmlTag, $imageUrl, $picture);
            }

            $picture = $picture->__toString();

            if ($picture === '') {
                continue;
            }

            $content = substr_replace($content, $picture, $offset, strlen($htmlTag));
            $accumulatedChange += (strlen($picture) - strlen($htmlTag));
        }

        return $content;
    }

    /**
     * Get exclude image attributes
     *
     * @return string
     */
    public function getExcludeImageAttributes(): string
    {
        return '/(.*data-nooptimize=\"true\".*|.*\/media\/captcha\/.*)/mi';
    }
}
