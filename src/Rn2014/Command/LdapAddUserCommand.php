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

class LdapAddUserCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ldap:add:user')
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

            $ldap->setPathScripts(LDAP_PATH_SCRIPTS);

            $result = $ldap->addUser($type, $username, $name, $password);

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
