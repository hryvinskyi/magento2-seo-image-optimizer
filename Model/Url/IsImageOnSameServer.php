<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Url;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class IsImageOnSameServer implements IsImageOnSameServerInterface
{
    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @inerhitDoc
     */
    public function execute(string $imageUrl): bool
    {
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $staticUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $mediaUrlWithoutBaseUrl = str_replace($baseUrl, '', $mediaUrl);

        return !(strpos($imageUrl, $mediaUrl) === false &&
            strpos($imageUrl, $mediaUrlWithoutBaseUrl) === false &&
            strpos($imageUrl, $staticUrl) === false);
    }
}
