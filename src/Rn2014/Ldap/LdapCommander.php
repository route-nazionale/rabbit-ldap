<?php
/**
 * User: lancio
 * Date: 07/07/14
 * Time: 23:22
 */

namespace Rn2014\Ldap;


class LdapCommander {

    private $ldap;
    private $path_scripts = "/root/";

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
                $command = "add_oneteam_user.sh";
                break;
            case 'rs':
                $command = "add_rs_user.sh";
                break;
            case 'test':
                $command = "add_test_user.sh";
                break;
            default:
                throw new \Exception("group Type [$groupType] not found! (oneteam|rs) ");
                break;
        }

        $params = sprintf(' "%s" "%s" "%s" ',
            escapeshellarg($username),
            escapeshellarg($complete_name),
            escapeshellarg($password));

        if (!is_file($this->path_scripts  . $command)) {
            return ['response' => false, 'errors' => ["Script [$command] not found"]];
        }

        exec($this->path_scripts  . $command . $params, $output);

        return 0 === count($output)? ['response' => true] : ['response' => false, 'errors' => $output];
    }

    public function changePassword($username, $old_password, $password)
    {
        $this->ldap->bindAdmin();

        $response = $this->ldap->changePassword($username, $old_password, $password);

        return $response;
    }
}