<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model;

use Hryvinskyi\ResponsiveImages\Module\ImageInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\PictureInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\SourceInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\SrcInterfaceFactory;
use Hryvinskyi\SeoImageOptimizer\Model\Url\IsImageOnSameServerInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\ConfigInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\Convertor\ConvertorListing;
use Hryvinskyi\SeoImageOptimizerApi\Model\ImageParserInterface;
use Psr\Log\LoggerInterface;

class ImageParser implements ImageParserInterface
{
    private ConvertorListing $convertorListing;
    private PictureInterfaceFactory $pictureFactory;
    private SourceInterfaceFactory $sourceFactory;
    private SrcInterfaceFactory $srcFactory;
    private ImageInterfaceFactory $imageFactory;
    private IsImageOnSameServerInterface $isImageOnSameServer;
    private ConfigInterface $config;
    private LoggerInterface $logger;
    private string $imgRegex;
    private string $sourceRegex;
    private string $sourceImgRegex;

    /**
     * @param ConvertorListing $convertorListing
     * @param PictureInterfaceFactory $pictureFactory
     * @param SourceInterfaceFactory $sourceFactory
     * @param SrcInterfaceFactory $srcFactory
     * @param ImageInterfaceFactory $imageFactory
     * @param IsImageOnSameServerInterface $isImageOnSameServer
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     * @param string $imgRegex
     * @param string $sourceRegex
     * @param string $sourceImgRegex
     */
    public function __construct(
        ConvertorListing $convertorListing,
        PictureInterfaceFactory $pictureFactory,
        SourceInterfaceFactory $sourceFactory,
        SrcInterfaceFactory $srcFactory,
        ImageInterfaceFactory $imageFactory,
        IsImageOnSameServerInterface $isImageOnSameServer,
        ConfigInterface $config,
        LoggerInterface $logger,
        string $imgRegex = '/<img([^<]+\s|\s)src=(\"|' . "\')([^<]+?\.(png|jpg|jpeg))[^<]+>(?!(<\/pic|\s*<\/pic))/mi",
        string $sourceRegex = '/<source([^<]+\s|\s)srcset=(\"|' . "\')([^<]+?\.(png|jpg|jpeg))[^<]+>(?!(<\/pic|\s*<\/pic))/mi",
        string $sourceImgRegex = '/(http(s?):)([\/|.|\w|\s|-])*\.(?:png|jpg|jpeg)/mi'
    ) {
        $this->convertorListing = $convertorListing;
        $this->pictureFactory = $pictureFactory;
        $this->sourceFactory = $sourceFactory;
        $this->srcFactory = $srcFactory;
        $this->imageFactory = $imageFactory;
        $this->isImageOnSameServer = $isImageOnSameServer;
        $this->config = $config;
        $this->logger = $logger;
        $this->imgRegex = $imgRegex;
        $this->sourceRegex = $sourceRegex;
        $this->sourceImgRegex = $sourceImgRegex;
    }

    /**
     * @param string $content
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $content): string
    {
        $content = $this->processPictureTag($content);
        $content = $this->processImgTag($content);

        return $content;
    }

    /**
     * @param string $content
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function processImgTag(string $content): string
    {
        if (preg_match_all($this->imgRegex, $content, $images, PREG_OFFSET_CAPTURE) === false) {
            return $content;
        }

        $accumulatedChange = 0;
        $excludeImageAttributes = $this->getExcludeImageAttributes();
        foreach ($images[0] as $index => $image) {
            $offset = $image[1] + $accumulatedChange;
            $htmlTag = $images[0][$index][0];
            $imageUrl = $images[3][$index][0];

            /**
             * Skip when image is not from same server
             */
            if ($this->isImageOnSameServer->execute($imageUrl) === false) {
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
                if ($outputUrl = $convertor->execute($imageUrl)) {
                    $source = $this->sourceFactory->create();
                    $src = $this->srcFactory->create();
                    $picture->addSource($source->addSrc($src->setUrl($outputUrl)));
                }
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
     * @param string $content
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function processPictureTag(string $content): string
    {
        if (preg_match_all($this->sourceRegex, $content, $sources, PREG_OFFSET_CAPTURE) === false) {
            return $content;
        }

        $accumulatedChange = 0;
        $excludeImageAttributes = $this->getExcludePictureAttributes();
        foreach ($sources[0] as $index => $source) {

            if (preg_match_all($this->sourceImgRegex, $source[0], $links, PREG_OFFSET_CAPTURE) === false) {
                continue;
            }

            $offset = $source[1] + $accumulatedChange;
            $newSource = $htmlTag = $sources[0][$index][0];
            $newSources = [];
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

            foreach ($links[0] as $link) {
                $imageUrl = $link[0];

                /**
                 * Skip when image is not from same server
                 */
                if ($this->isImageOnSameServer->execute($imageUrl) === false) {
                    continue;
                }

                foreach ($this->convertorListing->getConvertors() as $key => $convertor) {
                    if (isset($newSources[$key]) === false) {
                        $newSources[$key] = $newSource;
                    }

                    if ($outputUrl = $convertor->execute($imageUrl)) {
                        $newSources[$key] = str_replace($imageUrl, $outputUrl, $newSources[$key]);
                    }
                }
            }

            $source = implode(PHP_EOL, $newSources) . PHP_EOL . $htmlTag;

            $content = substr_replace($content, $source, $offset, strlen($htmlTag));
            $accumulatedChange += (strlen($source) - strlen($htmlTag));
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
        return '/(' . implode('|', $this->config->getExcludeImageExpressionList()) . ')/mi';
    }

    /**
     * Get exclude picture attributes
     *
     * @return string
     */
    public function getExcludePictureAttributes(): string
    {
        return '/(' . implode('|', $this->config->getExcludePictureExpressionList()) . ')/mi';
    }
}
