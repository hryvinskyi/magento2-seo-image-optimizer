<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

class Avif extends CmdAbstractConvertor
{
    /**
     * @inheritDoc
     */
    public function cmd(string $inputPath, string $outputPath): string
    {
        $cmd = $this->getDir()->getDir('Hryvinskyi_SeoImageOptimizer') . '/bin/cavif';
        return $this->escapeShellArg($cmd) . ' "' . $inputPath . '" -Q ' . $this->getConfig()->imageQuality()
            . ' -o "' . $outputPath . '"';
    }

    /**
     * @inheritDoc
     */
    public function imageType(): string
    {
        return 'avif';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->getConfig()->isEnabledAvif();
    }
}
