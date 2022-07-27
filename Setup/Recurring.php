<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Setup;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Recurring implements InstallSchemaInterface
{
    private Dir $moduleDir;
    private DriverInterface $filesystem;

    public function __construct(Dir $moduleDir, DriverInterface $filesystem)
    {
        $this->moduleDir = $moduleDir;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     * @throws FileSystemException
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();
        $dir = $this->moduleDir->getDir('Hryvinskyi_SeoImageOptimizer');
        $this->filesystem->changePermissionsRecursively($dir . '/bin/', 0755, 0777);
        $setup->endSetup();
    }
}
