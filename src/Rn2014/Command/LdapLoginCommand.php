<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:44
 */

namespace Rn2014\Command;

use Rn2014\Ldap\LdapRawCaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LdapLoginCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('ldap:login')
            ->setDescription('effettua il login dell\'utente')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'username'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username  = $input->getArgument('username');
        $password = $input->getArgument('password');

        $params = array(
            'hostname'      => LDAP_HOST,
            'port'          => LDAP_PORT,
            'security'      => LDAP_SECURITY,
            'base_dn'       => LDAP_BASE_DN,
            'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION]
        );

        $ldap = new LdapRawCaller($params);

        $ldap->bindAnonimously();

        // Ready for searching & persisting information
        try {
            $dn = $ldap->getDn($username);
            $output->writeln(ldap_dn2ufn($dn));
        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        try {

            $ok = $ldap->bindByUsername($username, $password);
            $output->writeln("logged: ".($ok?"true":"false"));

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }


        $output->writeln("finish");
    }
}