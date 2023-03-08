<?php
/**
 * Copyright (c) 2022. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoImageOptimizer\Setup;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class Recurring implements InstallSchemaInterface
{
    private Dir $moduleDir;
    private File $filesystem;
    private DirectoryList $directoryList;

    public function __construct(Dir $moduleDir, File $filesystem, DirectoryList $directoryList)
    {
        $this->moduleDir = $moduleDir;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
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
        $dirVar = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/hryvinskyi/bin/';
        $dirModule = $this->moduleDir->getDir('Hryvinskyi_SeoImageOptimizer') . '/bin/';
        try {
            $this->filesystem->deleteDirectory($dirVar);
        } catch (\Exception $e) {}

        $this->filesystem->createDirectory($dirVar);
        $this->filesystem->copy($dirModule . 'cavif', $dirVar . 'cavif');
        $this->filesystem->copy($dirModule . 'cwebp', $dirVar . 'cwebp');
        $this->filesystem->copy($dirModule . 'magick', $dirVar . 'magick');
        $this->filesystem->changePermissionsRecursively(
            $dirVar,
            0755,
            0777
        );
        $setup->endSetup();
    }
}
