<?php

namespace Ryvon\EventLogFlags\Cron;

use Ryvon\EventLogFlags\Helper\DatabaseManager;
use Ryvon\EventLogFlags\Model\Config;

/**
 * Cron handler to update the location database.
 */
class UpdateLocationDatabaseCronHandler
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param Config $config
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        Config $config,
        DatabaseManager $databaseManager
    ) {
        $this->config = $config;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Executes the cron job.
     */
    public function execute()
    {
        if (!$this->config->getEnableCountryFlags() || !$this->config->getEnableCountryUpdate()) {
            return;
        }

        if (!$this->databaseManager->needsUpdate()) {
            return;
        }

        $this->databaseManager->updateDatabase();
    }
}
