<?php

namespace Ryvon\EventLogFlags\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Ryvon\EventLogFlags\Helper\DatabaseManager;

/**
 * Installs the country database if not available.
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param DatabaseManager $databaseManager
     */
    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * @inheritDoc
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if ($this->databaseManager->needsUpdate()) {
            $this->databaseManager->updateDatabase();
        }
    }
}
