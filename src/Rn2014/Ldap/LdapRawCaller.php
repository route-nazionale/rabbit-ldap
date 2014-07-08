<?php
/**
 * User: lancio
 * Date: 04/07/14
 * Time: 22:40
 * @author Shawn Grover 2008
 * @author Luca Lancioni
 *
 */

namespace Rn2014\Ldap;

use Rn2014\Entity\User;

class LdapRawCaller {

    private $connection;
    private $baseDn;
    private $adminDn = false;
    private $adminPassword = false;

    public function __construct($params)
    {
        $this->baseDn = $params["base_dn"];

        $schema = "ldap://";
        if ("SSL" === $params["security"]) {
            $schema = "ldaps://";
        }
        $url = $schema . $params["hostname"];
        $this->connection = ldap_connect($url , $params["port"]);

        foreach($params["options"] as $option => $value) {
            ldap_set_option($this->connection, $option, $value);
        }

        if (isset($params["admin"])) {
            $this->adminDn = $params["admin"]["dn"];
            $this->adminPassword = $params["admin"]["password"];
        }
    }

    public function setPasswordEncrypter(PasswordEncrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    public function getDn($username)
    {
        $dn = false;
        $s = ldap_search($this->connection, "ou=Users,".$this->baseDn, "uid=$username");
        $count = ldap_count_entries($this->connection, $s);
        $entry = ldap_first_entry($this->connection, $s);
        if ($entry) {
            $dn = ldap_get_dn($this->connection, $entry);
        }

        return $dn;
    }

    public function bindAnonymously()
    {
        return ldap_bind($this->connection);
    }

    public function login($dn , $password)
    {
        $this->bind($dn , $password);
    }

    public function bind($dn, $password)
    {
        return @ldap_bind($this->connection, $dn, $password);
    }

    public function bindAdmin()
    {
        if (!$this->adminDn) {
            throw new \Exception("admin account not configured");
        }
        return @ldap_bind($this->connection, $this->adminDn, $this->adminPassword);
    }

    public function testPassword($dn, $password)
    {

//    //        echo $dn;
//            $attr = "userPassword";
//
//            // compare value
//            $r = ldap_compare($this->connection, $dn, $attr, $password);
//
//            if ($r === -1) {
//                $this->ldapError();
//                return false;
//            }
//
//            return $r;

            $r = $this->bind($dn, $password);
            return $r;

    }

    public function isUserInGroup($username, $group)
    {
        $filter = "(&(cn=$group)(memberUid=$username))";
        $dnGroup = "ou=Groups," . $this->baseDn;
        $result = $this->search($dnGroup, $filter);
        $count = $result["count"];

        if ($count > 0) {
            return true;
        }

        return false;
    }

    /**
     * Search an LDAP server
     */
    public function search($dn, $filter, $attributes = null)
    {
        if ($attributes) {
            $results = ldap_search($this->connection, $dn, $filter, $attributes);
        } else {
            $results = ldap_search($this->connection, $dn, $filter);
        }

        if ($results) {
            $entries = ldap_get_entries($this->connection, $results);
            return $entries;
        }

        return ['count' => 0];
    }

    /**
     * throw an error, getting the needed info from the connection object
     */
    public function ldapError()
    {
        throw new \Exception(
            'Error: ('. ldap_errno($this->connection) .') '. ldap_error($this->connection)
        );
    }

    /**
     * Convert an LDAP search result into an array
     */
    public function resultToArray($result)
    {
        $resultArray = array();

        if ($result)
        {
            $entry = ldap_first_entry($this->connection, $result);
            while ($entry)
            {
                $row = array();
                $attr = ldap_first_attribute($this->connection, $entry);
                while ($attr)
                {
                    $val = ldap_get_values_len($this->connection, $entry, $attr);
                    if (array_key_exists('count', $val) AND $val['count'] == 1)
                    {
                        $row[strtolower($attr)] = $val[0];
                    }
                    else
                    {
                        $row[strtolower($attr)] = $val;
                    }

                    $attr = ldap_next_attribute($this->connection, $entry);
                }
                $resultArray[] = $row;
                $entry = ldap_next_entry($this->connection, $entry);
            }
        }
        return $resultArray;
    }

    /**
     * throw an error, getting the needed info from the connection object
     */
    public function disconnect()
    {
        ldap_unbind($this->connection);
    }



    public function changePassword($user, $oldPassword, $newPassword)
    {
        $message = [];
        $dn = "ou=Users," . $this->baseDn;
        $this->bindAdmin();
        // bind anon and find user by uid
        $user_search = ldap_search($this->connection, $dn, "(|(uid=$user)(mail=$user))");
        $user_get = ldap_get_entries($this->connection, $user_search);
        $user_entry = ldap_first_entry($this->connection, $user_search);
        $user_dn = ldap_get_dn($this->connection, $user_entry);

        /* Start the testing */
        if (@ldap_bind($this->connection, $user_dn, $oldPassword) === false) {
            $message[] = "Error E101 - Current Username or Password is wrong.";
            return ['response' => false, 'errors' => $message];
        }
        $encoded_newPassword = "{SHA}" . base64_encode( pack( "H*", sha1( $newPassword ) ) );


        if (strlen($newPassword) < 8 ) {
            $message[] = "Error E103 - Your new password is too short.<br/>Your password must be at least 8 characters long.";
            return ['response' => false, 'errors' => $message];
        }
//        if (!preg_match("/[0-9]/",$newPassword)) {
//            $message[] = "Error E104 - Your new password must contain at least one number.";
//            return ['response' => false, 'errors' => $message];
//        }
//        if (!preg_match("/[a-zA-Z]/",$newPassword)) {
//            $message[] = "Error E105 - Your new password must contain at least one letter.";
//            return ['response' => false, 'errors' => $message];
//        }
//        if (!preg_match("/[A-Z]/",$newPassword)) {
//            $message[] = "Error E106 - Your new password must contain at least one uppercase letter.";
//            return ['response' => false, 'errors' => $message];
//        }
//        if (!preg_match("/[a-z]/",$newPassword)) {
//            $message[] = "Error E107 - Your new password must contain at least one lowercase letter.";
//            return ['response' => false, 'errors' => $message];
//        }
        if (!$user_get) {
            $message[] = "Error E200 - Unable to connect to server, you may not change your password at this time, sorry.";
            return ['response' => false, 'errors' => $message];
        }

        /* And Finally, Change the password */
        $entry = array();
        $entry["userPassword"] = "$encoded_newPassword";

        if (ldap_modify($this->connection, $user_dn, $entry) === false){
            $error = ldap_error($this->connection);
            $errno = ldap_errno($this->connection);
            $message[] = "E201 - Your password cannot be change, please contact the administrator.";
            $message[] = "$errno - $error";

            return ['response' => false, 'errors' => $message];
        } else {

            return ['response' => true];
        }
    }
}
