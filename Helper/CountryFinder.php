<?php

namespace Ryvon\EventLogCountryFlags\Helper;

use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Magento\Framework\ObjectManagerInterface;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Helper class to look up countries from IP addresses.
 */
class CountryFinder
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var DatabaseManager
     */
    private $fileManager;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param CountryFactory $countryFactory
     * @param DatabaseManager $fileManager
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        CountryFactory $countryFactory,
        DatabaseManager $fileManager,
        ObjectManagerInterface $objectManager
    ) {
        $this->countryFactory = $countryFactory;
        $this->fileManager = $fileManager;
        $this->objectManager = $objectManager;
    }

    /**
     * Returns the database reader instance.
     *
     * @return Reader|null
     */
    public function getReader()
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $this->loadReader();
        }

        return $this->reader;
    }

    /**
     * Creates the reader instance.  We don't do this in the constructor so it isn't initialized until it is needed.
     */
    private function loadReader()
    {
        try {
            $database = $this->fileManager->getLocalFile();
            if ($database) {
                $this->reader = $this->objectManager->create(Reader::class, [
                    'filename' => $database
                ]);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Retrieves the country info from the database for the specified IP address.
     *
     * @param string $ipAddress
     * @return Country|null
     */
    public function getCountry($ipAddress)
    {
        $reader = $this->getReader();
        if (!$reader) {
            return null;
        }

        try {
            $country = $reader->country($ipAddress);
            if (!$country) {
                return null;
            }

            return $this->countryFactory->create([
                'isoCode' => $country->country->isoCode,
                'name' => $country->country->name,
            ]);
        } catch (AddressNotFoundException $e) {
        } catch (InvalidDatabaseException $e) {
        }

        return null;
    }
}
