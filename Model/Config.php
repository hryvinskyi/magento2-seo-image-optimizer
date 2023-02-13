<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Model;

use Hryvinskyi\Logger\Model\DebugConfigInterface;
use Hryvinskyi\SeoImageOptimizerApi\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config implements ConfigInterface, DebugConfigInterface
{
    /**
     * Configuration paths
     */
    public const XML_CONF_ENABLED = 'hryvinskyi_seo/image_optimizer/enabled';
    public const XML_CONF_IMAGE_QUALITY = 'hryvinskyi_seo/image_optimizer/image_quality';
    public const XML_CONF_ENABLED_WEBP = 'hryvinskyi_seo/image_optimizer/enabled_webp';
    public const XML_CONF_ENABLED_AVIF = 'hryvinskyi_seo/image_optimizer/enabled_avif';
    public const XML_CONF_ENABLED_JPG2 = 'hryvinskyi_seo/image_optimizer/enabled_jpg2';
    public const XML_CONF_EXCLUDE_IMAGE_EXPRESSION = 'hryvinskyi_seo/image_optimizer/exclude_image_expression';
    public const XML_CONF_EXCLUDE_PICTURE_EXPRESSION = 'hryvinskyi_seo/image_optimizer/exclude_picture_expression';
    public const XML_CONF_DEBUG = 'hryvinskyi_seo/image_optimizer/debug';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_ENABLED);
    }

    /**
     * @return int
     */
    public function imageQuality(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_CONF_IMAGE_QUALITY);
    }

    /**
     * @inheritDoc
     */
    public function isDebug(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_DEBUG);
    }

    /**
     * @inheritDoc
     */
    public function isEnabledWebP(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_ENABLED_WEBP);
    }

    /**
     * @inheritDoc
     */
    public function isEnabledAvif(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_ENABLED_AVIF);
    }

    /**
     * @inheritDoc
     */
    public function isEnabledJpg2(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_CONF_ENABLED_JPG2);
    }

    /**
     * @inheritDoc
     */
    public function getExcludeImageExpressionList(): array
    {
        $list = (string)$this->scopeConfig->getValue(self::XML_CONF_EXCLUDE_IMAGE_EXPRESSION);

        $result = [];
        foreach (explode(PHP_EOL, $list) as $expression) {
            $expression = trim($expression);
            if ($expression) {
                $result[] = $expression;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getExcludePictureExpressionList(): array
    {
        $list = (string)$this->scopeConfig->getValue(self::XML_CONF_EXCLUDE_PICTURE_EXPRESSION);
        $result = [];
        foreach (explode(PHP_EOL, $list) as $expression) {
            $expression = trim($expression);
            if ($expression) {
                $result[] = $expression;
            }
        }

        return $result;
    }
}
