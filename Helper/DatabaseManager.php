<?php

namespace Ryvon\EventLogCountryFlags\Helper;

use DateInterval;
use DateTime;
use Exception;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * Helper class to manage the country database.
 */
class DatabaseManager
{
    /**
     * The URL to download the database from.
     */
    const COUNTRY_DB_URL = 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.mmdb.gz';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @param Curl $curl
     * @param DirectoryList $directoryList
     * @param File $file
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        DirectoryList $directoryList,
        File $file,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->file = $file;
        $this->logger = $logger;
        $this->rootPath = $directoryList->getRoot();
    }

    /**
     * Checks whether the database needs to be updated.
     *
     * @return bool
     */
    public function needsUpdate(): bool
    {
        try {
            $interval = new DateInterval('P6DT23H');
        } catch (Exception $e) {
            // If we can't even create an interval we don't want to update
            return false;
        }

        return $this->wasUpdatedWithin($interval);
    }

    /**
     * Returns the last updated timestamp.
     *
     * @return int|null
     */
    public function getLastUpdated()
    {
        $metaFile = $this->getMetaFile();
        if (!$this->file->fileExists($metaFile)) {
            return null;
        }

        $lastUpdated = $this->file->read($metaFile);
        if (!$lastUpdated) {
            return null;
        }

        return (int)$lastUpdated;
    }

    /**
     * Fetches the database from MaxMind.
     *
     * @return bool
     */
    public function updateDatabase(): bool
    {
        try {
            $timeoutInSeconds = 10;
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, $timeoutInSeconds);
            $this->curl->setOption(CURLOPT_TIMEOUT, $timeoutInSeconds);

            $this->curl->get(static::COUNTRY_DB_URL);

            $body = $this->curl->getBody();

            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $decoded = gzdecode($body);

            if ($decoded) {
                $this->logger->debug(sprintf(
                    'Downloaded %s bytes from %s, decompressed to %s bytes',
                    strlen($body),
                    static::COUNTRY_DB_URL,
                    strlen($decoded)
                ));

                $this->updateMetaFile();

                $localFile = $this->getLocalFile(false);
                $this->file->write($localFile, $decoded);
                return true;
            }

            $this->logger->debug(sprintf(
                'Failed to decode %s byte file downloaded from %s',
                strlen($body),
                static::COUNTRY_DB_URL
            ));
        } catch (Exception $e) {
            $this->logger->error(sprintf('Failed to download IP database from %s', static::COUNTRY_DB_URL));
            $this->logger->critical($e);
        }

        return false;
    }

    /**
     * Returns the local database file path.
     *
     * @param bool $onlyIfExists If true will check if the file exists.  If it does not will return null.
     * @return string|null
     */
    public function getLocalFile($onlyIfExists = true)
    {
        $file = sprintf('%s/GeoLite2-Country.mmdb', $this->getStoragePath());

        if (!$onlyIfExists) {
            return $file;
        }

        return $this->file->fileExists($file) ? $file : null;
    }

    /**
     * Checks whether the database was updated in the specified interval time.
     *
     * @param DateInterval $cutoff
     * @return bool
     */
    private function wasUpdatedWithin(DateInterval $cutoff): bool
    {
        $lastUpdated = $this->getLastUpdated();
        if ($lastUpdated === null || !preg_match('#^\d+$#', $lastUpdated)) {
            return true;
        }

        try {
            $updated = (new DateTime())->setTimestamp($lastUpdated);
            $now = new DateTime();
        } catch (Exception $e) {
            return false;
        }

        return $now->sub($cutoff) > $updated;
    }

    /**
     * Returns the local meta file path.
     *
     * @return string
     */
    private function getMetaFile(): string
    {
        return sprintf('%s.updated', $this->getLocalFile(false));
    }

    /**
     * Returns the root storage file path.
     *
     * @return string
     */
    private function getStoragePath(): string
    {
        return $this->rootPath . '/var/event-log';
    }

    /**
     * Updates the meta file with the current time.
     */
    private function updateMetaFile()
    {
        $localPath = $this->getStoragePath();
        if (!$this->file->fileExists($localPath)) {
            $this->file->mkdir($localPath, 0700);
        }

        $metaFile = $this->getMetaFile();
        $this->file->write($metaFile, (string)time());
    }
}
