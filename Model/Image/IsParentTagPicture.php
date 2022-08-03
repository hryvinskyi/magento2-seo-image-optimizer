<?php
/**
 * Copyright (c) 2022. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model\Image;

class IsParentTagPicture implements IsParentTagPictureInterface
{
    /**
     * @inheritDoc
     */
    public function execute(string $html, string $imageHTML): bool
    {
        $position = strpos($html, $imageHTML);
        $subHtml = substr($html, $position, 800);
        $length = strpos($subHtml, '</');
        $subHtml = substr($subHtml, $length);
        $length = strpos($subHtml, '>');
        $parentTag = substr($subHtml, 2, $length - 2);

        return $parentTag === 'picture';
    }
}
