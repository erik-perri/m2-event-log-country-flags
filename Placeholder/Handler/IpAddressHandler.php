<?php

namespace Ryvon\EventLogCountryFlags\Placeholder\Handler;

use Magento\Framework\DataObject;
use Ryvon\EventLogCountryFlags\Helper\CountryFinder;
use Ryvon\EventLogCountryFlags\Helper\FlagFinder;
use Ryvon\EventLogCountryFlags\Model\Config;

/**
 * Handler to prepend a flag to IP addresses handled by the ip-address handler.
 */
class IpAddressHandler extends \Ryvon\EventLog\Placeholder\Handler\IpAddressHandler
{
    /**
     * @var CountryFinder
     */
    private $countryFinder;

    /**
     * @var FlagFinder
     */
    private $flagFinder;

    /**
     * @param Config $config
     * @param CountryFinder $countryFinder
     * @param FlagFinder $flagFinder
     */
    public function __construct(
        Config $config,
        CountryFinder $countryFinder,
        FlagFinder $flagFinder
    ) {
        if ($config->getEnableCountryFlags()) {
            $this->countryFinder = $countryFinder;
            $this->flagFinder = $flagFinder;
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(DataObject $context)
    {
        $html = parent::handle($context);

        if (!$this->countryFinder || !$this->flagFinder || !$this->countryFinder->getReader()) {
            return $html;
        }

        $ipAddress = $context->getData('text');
        if ($html === null && $ipAddress) {
            // UserIpPlaceholder returns null when on a local IP address since it would not have added a link.
            // We handle it here instead since the replacer will not handle it once we return the flag icon.
            $html = htmlentities($ipAddress, ENT_QUOTES);
        }

        return $this->buildFlagTag($ipAddress ?: '') . $html;
    }

    /**
     * Builds the flag SVG container HTML.
     *
     * @param string $ipAddress
     * @return string|null
     */
    private function buildFlagTag(string $ipAddress)
    {
        $html = '';
        $title = '';

        if ($ipAddress) {
            $country = $this->countryFinder->getCountry($ipAddress);
            if ($country) {
                $title = sprintf('Country: %s', $country->getName());
                $html = $this->flagFinder->getFlagSvg($country->getIsoCode());
                if (!$html) {
                    $html = sprintf(
                        '<!-- Failed to find flag for country code "%s" -->',
                        $country->getIsoCode()
                    );
                }
            } else {
                $html = '<!-- Failed to find country -->';
            }
        }

        return $this->buildLinkTag([
            'html' => $html,
            'title' => $title,
            'class' => 'flag-container',
        ], 'div');
    }
}
