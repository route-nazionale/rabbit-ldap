<?php
/**
 * User: lancio
 * Date: 07/07/14
 * Time: 23:22
 */

namespace Rn2014\Ldap;


use Monolog\Logger;

class LdapCommander {

    private $ldap;
    private $path_scripts = "/bin/";

    public function __construct(LdapRawCaller $ldapCaller, Logger $logger)
    {
        $this->ldap = $ldapCaller;
        $this->logger = $logger;
    }

    public function setPathScripts($path)
    {
        $this->path_scripts = $path;
    }

    public function testLogin($username, $password)
    {
        $this->ldap->bindAnonymously();

        $dn = $this->ldap->getDn($username);

        $result = $this->ldap->testPassword($dn, $password);

        return $result;
    }

    public function isUserInGroup($username, $group)
    {
        return $this->ldap->isUserInGroup($username, $group);
    }

    public function attemptLogin($username, $password, $group)
    {
        $this->ldap->bindAnonymously();

        $dn = $this->ldap->getDn($username);

        $result = $this->ldap->bind($dn, $password);

        if (!$result) {
            return false;
        }

        $result = $this->ldap->isUserInGroup($username, $group);

        return $result;
    }

    public function attemptLoginWithBirthdate($username, $birthdate, $group)
    {
        return $this->attemptLogin($username, $birthdate, $group);
    }

    public function addUser($groupType, $username, $complete_name, $password)
    {
        $output = [];

        switch ($groupType) {
            case 'oneteam':
                $script = "add_oneteam_user.sh";
                break;
            case 'rs':
                $script = "add_rs_user.sh";
                break;
            case 'rscapi':
                $script = "add_rscapi_user.sh";
                break;
            case 'lab':
                $script = "add_lab_user.sh";
                break;
            case 'extra':
                $script = "add_extra_user.sh";
                break;
            case 'test':
                $script = "add_test_user.sh";
                break;
            default:
                throw new \Exception("group Type [$groupType] not found! (oneteam|rs|rscapi|lab|extra) ");
                break;
        }

        if (!is_file($this->path_scripts  . $script)) {
            return ['response' => false, 'errors' => [
                "Script [{$this->path_scripts}{$script}] not found",
                $this->path_scripts,
            ]];
        }

        $params = sprintf(
                    ' %s %s %s ',
                    escapeshellarg($username),
                    escapeshellarg($complete_name),
                    escapeshellarg($password)
        );
        $this->logger->addDebug($this->path_scripts  . $script . $params );
        exec($this->path_scripts  . $script . $params, $output);

        return 3 === count($output)? ['response' => true] : ['response' => false, 'errors' => $output];
    }

    public function changePassword($username, $old_password, $password)
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->changePassword($username, $old_password, $password);

        return $response;
    }

    public function resetPassword($username, $password)
    {
        $script = "change_pass.sh";

        if (!is_file($this->path_scripts  . $script)) {
            return ['response' => false, 'errors' => [
                "Script [{$this->path_scripts}{$script}] not found",
                $this->path_scripts,
            ]];
        }

        $params = sprintf(
            ' %s %s ',
            escapeshellarg($username),
            escapeshellarg($password)
        );

        exec($this->path_scripts  . $script . $params, $output);

        return 0 === count($output)? ['response' => true] : ['response' => false, 'errors' => $output];
    }

    public function userChangeGroup($username, $group, $add = true)
    {
        $this->ldap->bindAdmin();

        if ($add)
        $response = $this->ldap->userAddGroup($username, $group);
        else {
            $response = $this->ldap->userRemoveGroup($username, $group);
        }
        return $response;
    }

    public function removeUser($username, $password = "")
    {
        $this->ldap->bindAnonymously();

        $dn = $this->ldap->getDn($username);

        $this->ldap->bindAdmin();

        $response = $this->ldap->removeUser($dn);

        return $response;
    }

    public function disableUser($username)
    {
        return $this->ldap->changeUser($username, 0) === 0 ? ['response' => true]: ['response' => false, 'errors' => ['not modified']];

    }
    public function enableUser($username)
    {
        $this->ldap->changeUser($username, 1 ) === 1 ? ['response' => true]: ['response' => false, 'errors' => ['not modified']];
    }

    public function getUserGroups($username)
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->getUserGroups($username);

        return $response;
    }

    public function getUsers()
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->getUsers();

        return $response;
    }

    public function getGroups()
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->getGroups();

        return $response;
    }

}
