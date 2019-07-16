<?php

namespace Ryvon\EventLogFlags\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration model.
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns the "Enable Country Flags" configuration value.
     *
     * @return bool
     */
    public function getEnableCountryFlags(): bool
    {
        $value = $this->scopeConfig->getValue(
            'system/event_log/enable_country_flags',
            ScopeInterface::SCOPE_WEBSITE
        );
        return $value > 0;
    }

    /**
     * Returns the "Automatically Update Country Database" configuration value.
     *
     * @return bool
     */
    public function getEnableCountryUpdate(): bool
    {
        $value = $this->scopeConfig->getValue(
            'system/event_log/enable_country_update',
            ScopeInterface::SCOPE_WEBSITE
        );
        return $value > 0;
    }
}
