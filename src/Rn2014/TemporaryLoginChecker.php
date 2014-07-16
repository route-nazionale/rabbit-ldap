<?php
/**
 * User: lancio
 * Date: 16/07/14
 * Time: 23:13
 */

namespace Rn2014;


class TemporaryLoginChecker {

    public function __construct($dbal)
    {
        $this->db = $dbal;
    }

    public function testLogin($username, $password)
    {
        $sql = "SELECT * FROM users WHERE cu = :username AND datanascita = :password";
        $result = $this->db->fetchAssoc($sql, [
            'username' => $username,
            'password' => $password
        ]);

        return ($result || 0);
    }

    public function attemptLogin($username, $decodedPassword, $group)
    {
        return $this->testLogin($username, $decodedPassword);
    }

    public function attemptLoginWithBirthdate($username, $decodedBirthdate, $group)
    {
        return $this->testLogin($username, $decodedBirthdate);
    }
} 