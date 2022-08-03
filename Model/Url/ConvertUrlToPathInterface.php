<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Url;

interface ConvertUrlToPathInterface
{
    /**
     * Convert url to path
     *
     * @param string $url
     * @return string
     */
    public function execute(string $url): string;
}
