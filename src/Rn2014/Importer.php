<?php
/**
 * User: lancio
 * Date: 01/08/14
 * Time: 23:18
 */

namespace Rn2014;

use Doctrine\DBAL\Driver\PDOStatement;
use Monolog\Logger;
use Rn2014\Ldap\LdapCommander;

class Importer {

    public function __construct(LdapCommander $ldap, $output, Logger $logger, $dryrun = true)
    {
        $this->ldap = $ldap;
        $this->output = $output;
        $this->logger = $logger;
        $this->dryrun = $dryrun;
    }

    public function import($type, PDOStatement $users)
    {
        $count = 0;
        while ($user = $users->fetch()) {
            $cu = $user['cu'];
            $name = $user['nome']. ' ' . $user['cognome'];
            $password = $user['datanascita'] ? $user['datanascita'] : substr(md5(uniqid()), 0, 10);;
            if ($this->dryrun) {
                $this->output->writeln("- $type - [$cu] [$name] [$password]");
                $count++;
                continue;
            }

            try {
                $result = $this->ldap->addUser($type, $cu, $name, $password);
                $this->logger->addDebug("add user ", ["result" => $result, 'data' => [$type, $cu, $name, $password]]);
                $count++;
            } catch (\Exception $e) {
                $this->output->writeln("EXCEPTION: " . $e->getMessage());
                $this->logger->addWarning("EXCEPTION: " . $e->getMessage(), ['data' => [$cu, $name, $password], 'humen' => $user]);
            }
        }
        return $count;
    }

    public function fixGroups(PDOStatement $users, array $groupsToAdd = [], array $groupsToRemove = [])
    {
        $count = 0;
        while ($user = $users->fetch()) {
            $cu = $user['cu'];

            if ($this->dryrun) {
                $this->output->writeln("- fix - [$cu] +[".implode(",",$groupsToAdd)."] -[".implode(",",$groupsToRemove)."]");
                $count++;
                continue;
            }
            try {
                $add = true;
                foreach($groupsToAdd as $group) {
                    $result = $this->ldap->userChangeGroup($cu,$group, $add);
                    $this->logger->addDebug("change group", ["result" => $result, 'data' => [$cu, $group, $add]]);
                }

                $add = false;
                foreach($groupsToRemove as $group) {
                    $result = $this->ldap->userChangeGroup($cu,$group, $add);
                    $this->logger->addDebug("change group", ["result" => $result, 'data' => [$cu, $group, $add]]);
                }

                $count++;
            } catch (\Exception $e) {
                $this->output->writeln("EXCEPTION: " . $e->getMessage());
                $this->logger->addWarning("EXCEPTION: " . $e->getMessage(), ['data' => [$cu, $group, $add], 'humen' => $user]);
            }
        }
        return $count;
    }
}