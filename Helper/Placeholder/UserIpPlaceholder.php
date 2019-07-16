<?php

namespace Ryvon\EventLogFlags\Helper\Placeholder;

use Ryvon\EventLog\Helper\Placeholder\UserIpPlaceholder as OriginalUserIpPlaceholder;
use Ryvon\EventLogFlags\Helper\CountryFinder;
use Ryvon\EventLogFlags\Helper\FlagFinder;
use Ryvon\EventLogFlags\Model\Config;

/**
 * Override the user-ip placeholder to prepend the flag if found.
 */
class UserIpPlaceholder extends OriginalUserIpPlaceholder
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
    public function getReplaceString($context)
    {
        $html = parent::getReplaceString($context);

        if (!$this->countryFinder || !$this->flagFinder || !$this->countryFinder->getReader()) {
            return $html;
        }

        if ($html === null && $context->getData('user-ip')) {
            // UserIpPlaceholder returns null when on a local IP address since it would not have added a link.
            // We handle it here instead since the replacer will not handle it once we return the flag container.
            $html = htmlentities($context->getData('user-ip'), ENT_QUOTES);
        }

        return $this->buildFlagTag($context->getData('user-ip')) . $html;
    }

    /**
     * Builds the flag SVG container HTML.
     *
     * @param string $ipAddress
     * @return string|null
     */
    private function buildFlagTag(string $ipAddress)
    {
        $country = $this->countryFinder->getCountry($ipAddress);
        if ($country) {
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

        return $this->buildLinkTag([
            'html' => $html,
            'title' => $country ? sprintf('Country: %s', $country->getName()) : '',
            'class' => 'flag-container',
        ], 'div');
    }
}
