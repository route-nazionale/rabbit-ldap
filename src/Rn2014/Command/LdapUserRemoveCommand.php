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

class LdapUserRemoveCommand extends Command
{
    public function __construct(LdapCommander $ldap, $name = null)
    {
        $this->ldap = $ldap;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ldap:user:remove')
            ->setDescription('Rimozione dell\'utente')
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
            'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION],
            'admin'         => [
                'dn'        => LDAP_ADMIN_DN,
                'password'  => LDAP_ADMIN_PASSWORD,
            ]
        );

        try {
            $ldapCaller = new LdapRawCaller($params);

            $response = $this->ldap->removeUser($username, $password);

            $output->writeln("user [$username] removed: " . ($response['response'] ? "OK":"KO"));
            if (!$response['response']) {
                foreach ($response['errors'] as $error) {
                    $output->writeln("error: " . $error);
                }
            }

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        $output->writeln("finish");
    }
}
