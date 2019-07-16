<?php

namespace Ryvon\EventLogFlags\Console\Command;

use DateTime;
use Exception;
use GeoIp2\Database\Reader;
use Ryvon\EventLogFlags\Helper\CountryFinder;
use Ryvon\EventLogFlags\Helper\DatabaseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to update the location database.
 */
class LocationDatabaseCommand extends Command
{
    /**
     * @var CountryFinder
     */
    private $countryFinder;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @param CountryFinder $countryFinder
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        CountryFinder $countryFinder,
        DatabaseManager $databaseManager
    ) {
        parent::__construct();

        $this->countryFinder = $countryFinder;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this->setName('event-log:location-database')
            ->setDescription('View the location database status')
            ->setDefinition([
                new InputOption('lookup', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
                new InputOption('update', null, InputOption::VALUE_NONE, 'Update the database if needed'),
                new InputOption('force', null, InputOption::VALUE_NONE, 'Force the update to proceed'),
            ]);

        parent::configure();
    }

    /**
     * Executes the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('lookup')) {
            $this->lookup($input->getOption('lookup'), $output);
            return;
        }

        if ($input->getOption('update')) {
            $this->update($input->getOption('force') ?: false, $output);
            return;
        }

        $file = $this->databaseManager->getLocalFile();
        if (!$file) {
            $output->writeln('Database file missing.  Run with --update to download it.');
            return;
        }

        $updated = 'Unknown';
        try {
            $updatedTime = $this->databaseManager->getLastUpdated();
            if ($updatedTime !== null) {
                $updated = (new DateTime())
                    ->setTimestamp($updatedTime)
                    ->format('Y-m-d H:i:s');
            }
        } catch (Exception $e) {
            $updated .= '; ' . $e->getMessage();
        }

        $output->writeln('Database file found');
        $output->writeln(sprintf(' - File: %s', $file));
        $output->writeln(sprintf(' - Updated: %s', $updated));

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $output->writeln(sprintf(' - Size: %s', filesize($file)));

        if ($input->getOption('verbose')) {
            $output->writeln('');

            $reader = $this->countryFinder->getReader();
            if ($reader) {
                $meta = $this->getMetaData($reader);

                $output->writeln('Database parsed successfully');
                foreach ($meta as $key => $value) {
                    $output->writeln(sprintf(' - %s: %s', $key, $value));
                }
            } else {
                $output->writeln('<error>Database parse failure</error>');
            }
        }
    }

    /**
     * Lookup the specified IP and report the results.
     *
     * @param array $ips
     * @param OutputInterface $output
     */
    private function lookup(array $ips, OutputInterface $output)
    {
        if (!$this->databaseManager->getLocalFile()) {
            $output->writeln('Cannot lookup IP, database file missing.');
            return;
        }

        if (!$this->countryFinder->getReader()) {
            $output->writeln('Cannot lookup IP, failed to load database.');
            return;
        }

        foreach ($ips as $ip) {
            $country = $this->countryFinder->getCountry($ip);
            if ($country) {
                $output->writeln(sprintf('Checking %s <info>Found</info>', $ip));
                $output->writeln(sprintf(' - ISO Code: %s', $country->getIsoCode()));
                $output->writeln(sprintf(' - Name: %s', $country->getName()));
            } else {
                $output->writeln(sprintf('Checking %s <error>Not found</error>', $ip));
            }
        }
    }

    /**
     * Update the database file.
     *
     * @param bool $force
     * @param OutputInterface $output
     */
    private function update(bool $force, OutputInterface $output)
    {
        $needsUpdate = $force;

        if (!$this->countryFinder->getReader()) {
            $needsUpdate = true;
        }

        if ($this->databaseManager->needsUpdate()) {
            $needsUpdate = true;
        }

        if (!$needsUpdate) {
            $output->writeln('Database update not needed.  Use --force to continue anyway.');
            return;
        }

        if ($this->databaseManager->updateDatabase()) {
            $output->writeln('<info>Database updated.</info>');
        } else {
            $output->writeln('<error>Failed to update database.</error>');
        }
    }

    /**
     * Retrieves the metadata from the Reader
     *
     * @param Reader $reader
     * @return array|null
     */
    private function getMetaData(Reader $reader)
    {
        $readerMeta = $reader->metadata();
        $returnMeta = [];

        foreach ([
                     'binaryFormatMajorVersion',
                     'binaryFormatMinorVersion',
                     'buildEpoch',
                     'databaseType',
                     'description',
                     'ipVersion',
                     'languages',
                     'nodeByteSize',
                     'nodeCount',
                     'recordSize',
                     'searchTreeSize',
                 ] as $key) {
            $value = $readerMeta->$key ?? null;
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                if (!isset($value['en'])) {
                    continue;
                }
                $value = $value['en'];
            }
            $returnMeta[$key] = $value;
        }

        return $returnMeta;
    }
}
