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

class LdapSyncGroupsOnDbCommand extends Command
{
    public function __construct($ldap, $db, $log, $name = null)
    {
        $this->ldap = $ldap;
        $this->db = $db;
        $this->log = $log;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setName('ldap:sync:groups')
            ->setDescription('aggiorna la lista dei gruppi posix sul db');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $groups = $this->ldap->getGroups();

            $sql = "SELECT `group` FROM posix ";
            $result = $this->db->fetchAll($sql);
            $allPosix = array_map(function($v){return $v['group'];}, $result);

            $diffToRemove = array_diff($allPosix, $groups);
            $diffToAdd = array_diff($groups, $allPosix);

            if (count($diffToAdd)) {
                foreach ($diffToAdd as $group) {
                    $this->db->insert('posix', [
                        $this->db->quoteIdentifier("group") => $group
                    ]);
                }
                $this->log->addNotice('added.groups', $diffToAdd);
            }

            if (count($diffToRemove)) {
                foreach ($diffToRemove as $group) {
                    $this->db->delete('posix', [
                        $this->db->quoteIdentifier("group") => $group
                    ]);
                }
                $this->log->addNotice('removed.groups', $diffToRemove);
            }

        } catch (\Exception $e) {

            $output->writeln($e->getMessage());
            exit;
        }

        $output->writeln("finish");
    }
}
