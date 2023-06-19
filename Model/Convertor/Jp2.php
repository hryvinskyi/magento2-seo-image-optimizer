<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

use Magento\Framework\App\Filesystem\DirectoryList;

class Jp2 extends CmdAbstractConvertor
{
    /**
     * @inheritDoc
     */
    public function cmd(string $inputPath, string $outputPath): array
    {
        $cmd = $this->getDirectoryList()->getPath(DirectoryList::VAR_DIR) . '/hryvinskyi/bin/magick';
        return [
            $cmd,
            $this->escapeShellArg($inputPath),
            '-quality',
            $this->getConfig()->imageQuality(),
            '-define',
            'jp2:rate=' . $this->getConfig()->imageQuality(),
            $this->escapeShellArg($outputPath),
        ];
    }

    /**
     * @inheritDoc
     */
    public function imageType(): string
    {
        return 'jp2';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->getConfig()->isEnabledJpg2();
    }
}
