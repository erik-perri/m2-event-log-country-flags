<?php

namespace Ryvon\EventLogCountryFlags\Helper;

/**
 * Country info class.
 */
class Country
{
    /**
     * @var string
     */
    private $isoCode;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $isoCode
     * @param string $name
     */
    public function __construct(string $isoCode, string $name)
    {
        $this->isoCode = $isoCode;
        $this->name = $name;
    }

    /**
     * Returns the country ISO code.
     *
     * @return string
     */
    public function getIsoCode(): string
    {
        return $this->isoCode;
    }

    /**
     * Returns the country name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
