<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:44
 */

namespace Rn2014\Command;

use Monolog\Logger;
use Rn2014\HumenRepository;
use Rn2014\Importer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportUserCommand extends Command
{
    protected $ldap;
    protected $conn;
    protected $logger;

    static protected $types = ["rs", "rscapi", "lab", "extra", "oneteam"];

    public function __construct($ldap, $db, Logger $log, $name = null)
    {
        $this->ldap = $ldap;
        $this->conn = $db;
        $this->logger = $log;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('import:user')
            ->setDescription('importa gli utenti su ldap')
            ->addOption(
                'dryrun',
                'd',
                InputOption::VALUE_NONE,
                'testa l\'invio'
            )
            ->addArgument(
                'usertype',
                InputArgument::IS_ARRAY,
                'Quali tipi di utenti importare (' . implode(" | ", self::$types) . ')'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun  = $input->getOption('dryrun');
        $userTypes = $input->getArgument('usertype');

        if ($dryRun) {
            $output->writeln("-- DRY RUN --");
        }

        if (in_array("all", $userTypes)) {
            $typesToImport = self::$types;
        } else {
            $typesToImport = array_intersect(self::$types, $userTypes);
        }

        $output->writeln("Import degli utenti dei gruppi: " . implode(" ", $typesToImport));
        $this->logger->addInfo("Import degli utenti dei gruppi: " . implode(" ", $typesToImport), ['types' => $typesToImport]);

        if (!count($typesToImport)) {
            $output->writeln("Nessun gruppo selezionato");
            $this->logger->addInfo("Nessun gruppo selezionato");

            return;
        }
        $repo = new HumenRepository($this->conn, $output);
        $importer = new Importer($this->ldap, $output, $this->logger, $dryRun);

        $this->ldap->setPathScripts(LDAP_PATH_SCRIPTS);
        $totals = 0;
        $counts = 0;
        foreach ($typesToImport as $type) {
            $total = $repo->countUsers($type);
            $stmtUsers = $repo->getUsers($type);

            $count = $importer->import($type, $stmtUsers);
            $totals += $total;
            $counts += $count;
            $output->writeln($type . " - totali: " . $total ." -  importati: " . $count);
            $this->logger->addInfo($type . " - totali: " . $total ." -  importati: " . $count);
        }

        $output->writeln("TOTALI SU DB: " . $totals . " - TOTALI IMPORTATI: " . $counts);
        $this->logger->addInfo("TOTALI SU DB: " . $totals . " - TOTALI IMPORTATI: " . $counts);

        $output->writeln("finito");
    }
}

