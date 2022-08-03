<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Image;

interface IsParentTagPictureInterface
{
    /**
     * Check if image is parent tag picture
     *
     * @param string $html
     * @param string $imageHTML
     *
     * @return bool
     */
    public function execute(string $html, string $imageHTML): bool;
}
