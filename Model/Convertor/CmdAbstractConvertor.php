<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

use Hryvinskyi\ResponsiveImages\Module\ImageInterface;
use Hryvinskyi\ResponsiveImages\Module\ImageInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\PictureInterface;
use Hryvinskyi\ResponsiveImages\Module\SourceInterface;
use Hryvinskyi\ResponsiveImages\Module\SourceInterfaceFactory;
use Hryvinskyi\ResponsiveImages\Module\SrcInterface;
use Hryvinskyi\ResponsiveImages\Module\SrcInterfaceFactory;
use Hryvinskyi\SeoImageOptimizerApi\Model\ConfigInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\Convertor\ConvertorInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\File\IsOriginalFileUpdatedInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class CmdAbstractConvertor implements ConvertorInterface
{
    private Dir $dir;
    private File $file;
    private DriverFile $driverFile;
    private Filesystem $filesystem;
    private StoreManagerInterface $storeManager;
    private ImageInterfaceFactory $imageFactory;
    private SourceInterfaceFactory $sourceFactory;
    private SrcInterfaceFactory $srcFactory;
    private ConfigInterface $config;
    private IsOriginalFileUpdatedInterface $isOriginalFileUpdated;
    private LoggerInterface $logger;

    /**
     * @param Dir $dir
     * @param File $file
     * @param DriverFile $driverFile
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ImageInterfaceFactory $imageFactory
     * @param SourceInterfaceFactory $sourceFactory
     * @param SrcInterfaceFactory $srcFactory
     * @param ConfigInterface $config
     * @param IsOriginalFileUpdatedInterface $isOriginalFileUpdated
     * @param LoggerInterface $logger
     */
    public function __construct(
        Dir $dir,
        File $file,
        DriverFile $driverFile,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        ImageInterfaceFactory $imageFactory,
        SourceInterfaceFactory $sourceFactory,
        SrcInterfaceFactory $srcFactory,
        ConfigInterface $config,
        IsOriginalFileUpdatedInterface $isOriginalFileUpdated,
        LoggerInterface $logger
    ) {
        $this->dir = $dir;
        $this->file = $file;
        $this->driverFile = $driverFile;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->imageFactory = $imageFactory;
        $this->sourceFactory = $sourceFactory;
        $this->srcFactory = $srcFactory;
        $this->config = $config;
        $this->isOriginalFileUpdated = $isOriginalFileUpdated;
        $this->logger = $logger;
    }

    /**
     * @inheirtDoc
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute(
        string $sourceImageTag,
        string $sourceImageUri,
        PictureInterface $picture,
        ?string $destinationImageUri = null
    ): void {
        if ($this->isEnabled() === false) {
            return;
        }

        $inputPath = $this->convertUrlToPath($sourceImageUri);
        $outputPath = $this->getOutputPath($inputPath, $this->imageType());
        $alreadyConverted = false;

        if ($this->isFileExists($outputPath)) {
            $alreadyConverted = true;

            if ($this->isOriginalFileUpdated->execute($inputPath, $outputPath)) {
                $this->driverFile->deleteFile($outputPath);
                $alreadyConverted = false;
            }
        }

        if ($alreadyConverted === false) {
            $outputPath = $this->convert($inputPath, $outputPath);
        }

        if ($inputPath === $outputPath) {
            return;
        }

        $outputUrl = $this->convertPathToUrl($outputPath);
        $picture->addSource($this->createSource()->addSrc($this->createSrc()->setUrl($outputUrl)));
    }

    /**
     * @param string $inputPath
     * @param string $outputPath
     * @return string
     */
    abstract public function cmd(string $inputPath, string $outputPath): string;

    /**
     * @return string
     */
    abstract public function imageType(): string;

    /**
     * @return bool
     */
    abstract public function isEnabled(): bool;

    /**
     * @param string $inputPath
     * @param string|null $outputPath
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function convert(string $inputPath, string $outputPath): string
    {
        $folder = $this->driverFile->getParentDirectory($outputPath);
        if (!$this->driverFile->isDirectory($folder)) {
            $this->file->mkdir($folder, 0775);
        }

        try {
            $this->file->rm($outputPath);
            /** @noinspection PhpParamsInspection */
            $process = new Process($this->cmd($inputPath, $outputPath));
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $this->logger->error($exception->getMessage());
            return $inputPath;
        }

        if ($this->isFileExists($outputPath)) {
            return $outputPath;
        }

        return $inputPath;
    }

    /**
     * @param string $url
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertUrlToPath(string $url): string
    {
        $store = $this->storeManager->getStore();
        $urlMedia = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $urlStatic = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $pathMedia = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $pathStatic = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath();

        return str_replace([$urlMedia, $urlStatic], [$pathMedia, $pathStatic], $url);
    }

    /**
     * @param string $path
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertPathToUrl(string $path): string
    {
        $store = $this->storeManager->getStore();
        $urlMedia = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $urlStatic = $store->getBaseUrl(UrlInterface::URL_TYPE_STATIC);
        $pathMedia = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $pathStatic = $this->filesystem->getDirectoryRead(DirectoryList::STATIC_VIEW)->getAbsolutePath();

        return str_replace([$pathMedia, $pathStatic], [$urlMedia, $urlStatic], $path);
    }

    /**
     * Is File Exists
     *
     * @param string $path
     *
     * @return bool
     */
    public function isFileExists(string $path): bool
    {
        return $this->file->fileExists($path);
    }

    /**
     * @param string $inputPath
     * @param string $type
     * @return string
     */
    private function getOutputPath(string $inputPath, string $type): string
    {
        $image = preg_replace('/\.(png|jpg|jpeg)$/i', '.' . $type, $inputPath);

        return str_replace(
            ['media/', 'static/frontend/'],
            ['media/' . $type . '_image/', 'static/frontend/' . $type . '_image/'],
            $image
        );
    }

    /**
     * Escape Shell Arg
     *
     * @param string $str
     *
     * @return string
     */
    public function escapeShellArg(string $str): string
    {
        return escapeshellarg($str);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return ImageInterface
     */
    public function createImage(): ImageInterface
    {
        return $this->imageFactory->create();
    }

    /**
     * @return SourceInterface
     */
    public function createSource(): SourceInterface
    {
        return $this->sourceFactory->create();
    }

    /**
     * @return SrcInterface
     */
    public function createSrc(): SrcInterface
    {
        return $this->srcFactory->create();
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * @return Dir
     */
    public function getDir(): Dir
    {
        return $this->dir;
    }
}
