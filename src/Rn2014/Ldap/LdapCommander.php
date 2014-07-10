<?php
/**
 * User: lancio
 * Date: 07/07/14
 * Time: 23:22
 */

namespace Rn2014\Ldap;


class LdapCommander {

    private $ldap;
    private $path_scripts = "/bin/";

    public function __construct(LdapRawCaller $ldapCaller)
    {
        $this->ldap = $ldapCaller;
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
            case 'test':
                $script = "add_test_user.sh";
                break;
            default:
                throw new \Exception("group Type [$groupType] not found! (oneteam|rs) ");
                break;
        }


        if (!is_file($this->path_scripts  . $script)) {
            return ['response' => false, 'errors' => [
                "Script [{$this->path_scripts}{$script}] not found",
                $this->path_scripts,
            ]];
        }

        $params = sprintf(
                    ' "%s" "%s" "%s" ',
                    escapeshellarg($username),
                    escapeshellarg($complete_name),
                    escapeshellarg($password)
        );

        exec($this->path_scripts  . $script . $params, $output);

        return 0 === count($output)? ['response' => true] : ['response' => false, 'errors' => $output];
    }

    public function changePassword($username, $old_password, $password)
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->changePassword($username, $old_password, $password);

        return $response;
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

    public function removeUser($username, $password)
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
}
