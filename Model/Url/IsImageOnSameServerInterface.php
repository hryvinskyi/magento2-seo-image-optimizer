<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Url;

interface IsImageOnSameServerInterface
{
    /**
     * Check if image is on same server
     *
     * @param string $imageUrl
     * @return bool
     */
    public function execute(string $imageUrl): bool;
}
