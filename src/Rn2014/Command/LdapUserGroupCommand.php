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

class LdapUserGroupCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ldap:user:group')
            ->setDescription('lega/slega un utente a un gruppo')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'username'
            )
            ->addArgument(
                'group',
                InputArgument::REQUIRED,
                'group'
            )
            ->addOption(
                'remove',
                'r',
                InputOption::VALUE_NONE,
                'se presente rimuove l\'associazione'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username  = $input->getArgument('username');
        $group = $input->getArgument('group');
        $remove = $input->getOption('remove');

        $params = array(
            'hostname'      => LDAP_HOST,
            'port'          => LDAP_PORT,
            'security'      => LDAP_SECURITY,
            'base_dn'       => LDAP_BASE_DN,
            'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION],
            'admin'         => [
                'dn'        => LDAP_ADMIN_DN,
                'password'  => LDAP_ADMIN_PASSWORD,
            ]
        );

        try {
            $ldapCaller = new LdapRawCaller($params);
            $ldap = new LdapCommander($ldapCaller);

            $response = $ldap->userChangeGroup($username, $group, !$remove);

            $output->writeln("logged: " . ($response['response'] ? "OK":"KO"));

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        $output->writeln("finish");
    }
}
