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

class LdapUserGroupsCommand extends Command
{
    public function __construct($ldap, $name = null)
    {
        $this->ldap = $ldap;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ldap:user:groups')
            ->setDescription('lista dei gruppi di un utente')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'username'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username  = $input->getArgument('username');

        try {

            $response = $this->ldap->getUserGroups($username);

            $output->writeln("groups: " . print_r($response, true));

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        $output->writeln("finish");
    }
}
