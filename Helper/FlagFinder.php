<?php

namespace Ryvon\EventLogFlags\Helper;

use Ryvon\EventLog\Helper\FileLocator;

/**
 * Helper class to retrieve a country flag SVG.
 */
class FlagFinder
{
    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * @param FileLocator $locator
     */
    public function __construct(FileLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Returns the contents of the specified image file.
     *
     * @param string $isoCode
     * @return string|null
     */
    public function getFlagSvg(string $isoCode)
    {
        $imageName = sprintf('%s.svg', strtolower($isoCode));
        return $this->locator->getVendorFileContents(
            'components/flag-icon-css/flags/4x3',
            $imageName
        );
    }
}
