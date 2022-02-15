<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\File;

use Hryvinskyi\SeoImageOptimizerApi\Model\File\IsOriginalFileUpdatedInterface;
use Magento\Framework\Filesystem\Driver\File as DriverFile;
use Magento\Framework\Filesystem\Io\File as IoFile;

class IsOriginalFileUpdated implements IsOriginalFileUpdatedInterface
{
    private DriverFile $driverFile;
    private IoFile $ioFile;

    /**
     * @param DriverFile $driverFile
     * @param IoFile $ioFile
     */
    public function __construct(DriverFile $driverFile, IoFile $ioFile)
    {
        $this->driverFile = $driverFile;
        $this->ioFile = $ioFile;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $original, string $generated): bool
    {
        if ($this->ioFile->fileExists($original) && $this->ioFile->fileExists($generated)) {
            $originalModifyTime = $this->driverFile->stat($original)['mtime'];
            $generatedFileModifyTime = $this->driverFile->stat($generated)['mtime'];

            if ($originalModifyTime && $generatedFileModifyTime && $originalModifyTime > $generatedFileModifyTime) {
                return true;
            }
        }

        return false;
    }
}
