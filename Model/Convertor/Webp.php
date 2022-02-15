<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Convertor;

use Hryvinskyi\ResponsiveImages\Module\PictureInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\Convertor\ConvertorInterface;

class Webp extends CmdAbstractConvertor
{
    /**
     * @param string $inputPath
     * @param string $outputPath
     * @return string
     */
    public function cmd(string $inputPath, string $outputPath): string
    {
        $cmd = $this->getDir()->getDir('Hryvinskyi_SeoImageOptimizer') . '/bin/cwebp';
        return $this->escapeShellArg($cmd) . ' "' . $inputPath . '" -q ' . $this->getConfig()->imageQuality()
            . ' -alpha_q 100 -z 9 -m 6 -segments 4 -sns 80 -f 25 -sharpness 0 -strong -pass 10 -mt -alpha_method 1'
            . ' -alpha_filter fast  -o "' . $outputPath . '"';
    }

    /**
     * @inheritDoc
     */
    public function imageType(): string
    {
        return 'webp';
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->getConfig()->isEnabledWebP();
    }
}
