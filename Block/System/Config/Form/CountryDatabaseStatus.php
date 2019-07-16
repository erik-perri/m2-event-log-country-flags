<?php

namespace Ryvon\EventLogFlags\Block\System\Config\Form;

use DateTime;
use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Ryvon\EventLogFlags\Helper\DatabaseManager;
use Ryvon\EventLogFlags\Model\Config;

/**
 * Configuration frontend model to insert the database status into the comment.
 */
class CountryDatabaseStatus extends Field
{
    /**
     * The placeholder to look for in the field comment.  It will be replaced with the status.
     */
    const CONFIG_COMMENT_PLACEHOLDER = '<database-status />';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param Context $context
     * @param Config $config
     * @param DatabaseManager $databaseManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        DatabaseManager $databaseManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->databaseManager = $databaseManager;
    }

    /**
     * @inheritDoc
     */
    protected function _renderValue(AbstractElement $element)
    {
        $valueCell = parent::_renderValue($element);

        if (strpos($valueCell, static::CONFIG_COMMENT_PLACEHOLDER) === false) {
            return $valueCell;
        }

        return str_replace(
            static::CONFIG_COMMENT_PLACEHOLDER,
            $this->getStatus(),
            $valueCell
        );
    }

    /**
     * Returns the database status.
     *
     * @return string
     */
    private function getStatus(): string
    {
        if ($this->databaseManager->getLocalFile()) {
            $lastUpdated = $this->databaseManager->getLastUpdated();
            if ($lastUpdated !== null) {
                try {
                    $date = (new DateTime())
                        ->setTimestamp($lastUpdated)
                        ->format('Y-m-d g:i A');
                } catch (Exception $e) {
                    $date = 'unknown';
                }

                $message = sprintf('Country database found, last updated: %s.', $date);
            } else {
                $message = 'Country database found.';
            }
        } else {
            $message = '<strong>Flags will not show until the country database has been downloaded.</strong>';
            if ($this->config->getEnableCountryFlags()) {
                $message .= ' You must either run the country database update command (shown below) or wait until the'
                    . ' automatic updater runs before flags will begin to show.';
            } else {
                $message .= ' If you enable this you must either run the country database update command (shown below'
                    . ' after enabling) or wait until the automatic updater runs before flags will begin to show.';
            }
        }

        return $message;
    }
}
