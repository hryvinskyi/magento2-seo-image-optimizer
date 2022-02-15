<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

class Jp2 extends CmdAbstractConvertor
{
    /**
     * @inheritDoc
     */
    public function cmd(string $inputPath, string $outputPath): string
    {
        $cmd = $this->getDir()->getDir('Hryvinskyi_SeoImageOptimizer') . '/bin/magick';
        return $this->escapeShellArg($cmd) . ' "' . $inputPath . '" "' . $outputPath . '"';
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
