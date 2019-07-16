<?php

namespace Ryvon\EventLogCountryFlags\Cron;

use Ryvon\EventLogCountryFlags\Helper\DatabaseManager;
use Ryvon\EventLogCountryFlags\Model\Config;

/**
 * Cron handler to update the country database.
 */
class UpdateCountryDatabaseCronHandler
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
