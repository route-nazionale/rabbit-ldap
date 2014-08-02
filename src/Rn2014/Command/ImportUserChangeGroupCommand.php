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

class ImportUserChangeGroupCommand extends Command
{
    protected $ldap;
    protected $conn;
    protected $logger;

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
            ->setName('import:fix:users')
            ->setDescription('sposta gli utenti oneteam importati come rs su ldap a causa di un errore')
            ->addOption(
                'dryrun',
                'd',
                InputOption::VALUE_NONE,
                'testa l\'invio'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun  = $input->getOption('dryrun');

        if ($dryRun) {
            $output->writeln("-- DRY RUN --");
        }

        $output->writeln("Change group degli utenti oneteam da rs a oneteam");
        $this->logger->addInfo("Change group degli utenti oneteam da rs a oneteam");

        $repo = new HumenRepository($this->conn, $output);
        $importer = new Importer($this->ldap, $output, $this->logger, $dryRun);

        $this->ldap->setPathScripts(LDAP_PATH_SCRIPTS);
        $totals = 0;
        $counts = 0;

        $total = $repo->countWrongUsers();
        $stmtUsers = $repo->getWrongUsers();

        $groupsToRemove = ["rs"];
        $groupsToAdd = ["sys.users","plugdev","fuse"];
        $count = $importer->fixGroups($stmtUsers, $groupsToAdd, $groupsToRemove);

        $output->writeln("fix" . " - totali: " . $total ." -  corretti: " . $count);
        $this->logger->addInfo("fix" . " - totali: " . $total ." -  corretti: " . $count);

        $output->writeln("finito");
    }
}

