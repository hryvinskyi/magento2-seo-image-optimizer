<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Url;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConvertUrlToPath implements ConvertUrlToPathInterface
{
    private StoreManagerInterface $storeManager;
    private Filesystem $filesystem;

    public function __construct(StoreManagerInterface $storeManager, Filesystem $filesystem)
    {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $url): string
    {
        $store = $this->storeManager->getStore();
        $urlMedia = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $urlStatic = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $pathMedia = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $pathStatic = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath();

        return str_replace([$urlMedia, $urlStatic], [$pathMedia, $pathStatic], $url);
    }
}
