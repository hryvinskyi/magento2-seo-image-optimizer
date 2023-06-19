<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

use Hryvinskyi\SeoImageOptimizer\Model\Url\ConvertPathToUrlInterface;
use Hryvinskyi\SeoImageOptimizer\Model\Url\ConvertUrlToPathInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\ConfigInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\Convertor\ConvertorInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\File\IsOriginalFileUpdatedInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class CmdAbstractConvertor implements ConvertorInterface
{
    private Dir $dir;
    private File $file;
    private DriverFile $driverFile;
    private ConfigInterface $config;
    private IsOriginalFileUpdatedInterface $isOriginalFileUpdated;
    private ConvertPathToUrlInterface $convertPathToUrl;
    private ConvertUrlToPathInterface $convertUrlToPath;
    private DirectoryList $directoryList;
    private LoggerInterface $logger;

    public function __construct(
        Dir $dir,
        File $file,
        DriverFile $driverFile,
        ConfigInterface $config,
        IsOriginalFileUpdatedInterface $isOriginalFileUpdated,
        ConvertPathToUrlInterface $convertPathToUrl,
        ConvertUrlToPathInterface $convertUrlToPath,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->dir = $dir;
        $this->file = $file;
        $this->driverFile = $driverFile;
        $this->config = $config;
        $this->isOriginalFileUpdated = $isOriginalFileUpdated;
        $this->convertPathToUrl = $convertPathToUrl;
        $this->convertUrlToPath = $convertUrlToPath;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * @inheirtDoc
     */
    public function execute(string $sourceImageUri): ?string {
        if ($this->isEnabled() === false) {
            return null;
        }

        $inputPath = $this->convertUrlToPath->execute($sourceImageUri);
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
            return null;
        }

        return $this->convertPathToUrl->execute($outputPath);
    }

    /**
     * Run command
     *
     * @param string $inputPath
     * @param string $outputPath
     * @return string
     */
    abstract public function cmd(string $inputPath, string $outputPath): array;

    /**
     * Return image type
     *
     * @return string
     */
    abstract public function imageType(): string;

    /**
     * Check if convertor is enabled
     *
     * @return bool
     */
    abstract public function isEnabled(): bool;

    /**
     * Convert image
     *
     * @param string $inputPath
     * @param string $outputPath
     * @return string
     * @throws FileSystemException
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
     * Return output path
     *
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
     * Return Logger
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Return config
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Return dir
     *
     * @return Dir
     */
    public function getDir(): Dir
    {
        return $this->dir;
    }

    /**
     * Return DirectoryList
     *
     * @return DirectoryList
     */
    public function getDirectoryList(): DirectoryList
    {
        return $this->directoryList;
    }
}
