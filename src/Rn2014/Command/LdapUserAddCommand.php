<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:44
 */

namespace Rn2014\Command;

use Rn2014\Ldap\LdapCommander;
use Rn2014\Ldap\LdapRawCaller;
use Rn2014\Ldap\PasswordEncrypter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LdapUserAddCommand extends Command
{
    public function __construct($ldap, $name = null)
    {
        $this->ldap = $ldap;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ldap:user:add')
            ->setDescription('Nuovo utente')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'type of user (oneteam|rs|test)',
                "oneteam"
            )
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'username'
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'nome completo'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'nuova password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');

        $username  = $input->getArgument('username');
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');

        try {

            $this->ldap->setPathScripts(LDAP_PATH_SCRIPTS);

            $result = $this->ldap->addUser($type, $username, $name, $password);

            $output->writeln("new user: " . ($result['response'] ? "OK":"KO"));

            if (!$result['response']) {
                var_dump($result['errors']);
            }

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        $output->writeln("finish");
    }
}
