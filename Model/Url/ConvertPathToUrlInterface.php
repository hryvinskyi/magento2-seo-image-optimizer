<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Url;

interface ConvertPathToUrlInterface
{
    /**
     * Convert path to url
     *
     * @param string $path
     * @return string
     */
    public function execute(string $path): string;
}
