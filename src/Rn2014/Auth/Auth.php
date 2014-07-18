<?php
/**
 * User: lancio
 * Date: 18/07/14
 * Time: 01:16
 */

namespace Rn2014\Auth;

use Doctrine\DBAL\Connection;
use Rn2014\Ldap\LdapCommander;

class Auth implements AuthInterface
{

    public function __construct(Connection $dbal, LdapCommander $ldap)
    {
        $this->db = $dbal;
        $this->ldap = $ldap;
    }

    public function testLogin($username, $password)
    {
        return $this->ldap->testLogin($username, $password);
    }

    public function attemptLogin($username, $decodedPassword, $group)
    {
        return $this->ldap->attemptLogin($username, $decodedPassword, $group);
    }

    public function attemptLoginWithBirthdate($username, $decodedBirthdate, $group)
    {
        $result = $this->ldap->isUserInGroup($username, $group);

        if (!$result) {
            return false;
        }

        $sql = "SELECT * FROM humen_personali WHERE cu = :username AND datanascita = :password";
        $result = $this->db->fetchAssoc($sql, [
            'username' => $username,
            'password' => $decodedBirthdate
        ]);

        return ($result || 0);
    }
} 