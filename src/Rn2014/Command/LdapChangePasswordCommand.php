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

class LdapChangePasswordCommand extends Command
{
    public function __construct($ldap, $name = null)
    {
        $this->ldap = $ldap;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ldap:change:password')
            ->setDescription('Modifica la password dell\'utente')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'username'
            )
            ->addArgument(
                'old_password',
                InputArgument::REQUIRED,
                'vecchia password'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'nuova password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username  = $input->getArgument('username');
        $old_password = $input->getArgument('old_password');
        $password = $input->getArgument('password');

        try {

            $response = $this->ldap->changePassword($username, $old_password, $password);

            $output->writeln("change password: " . ($response['response'] ? "OK":"KO"));
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
