<?php
/**
 * User: lancio
 * Date: 04/07/14
 * Time: 22:40
 */

namespace Rn2014\Ldap;

use Rn2014\Entity\User;

class LdapRawCaller {

    private $connection;
    private $baseDn;

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

//    public function isUserInGroup($group, $username)
//    {
//        $results = $this->manager->search("ou=Groups,".$this->baseDn, '(&(cn='.$group.')(memberUid='.$username.'))', true);
//
//        var_dump($results->next() );
//    }

    public function bindAnonimously()
    {
        return ldap_bind($this->connection);
    }

    public function login($dn , $password)
    {
        $this->bind($dn , $password);
    }

    public function bind($dn, $password)
    {
        return ldap_bind($this->connection, $dn, $password);
    }

    public function bindByUsername($username , $password)
    {
        return @ldap_bind($this->connection, "uid=" . $username . ",ou=Users,dc=rn2014,dc=it", $password);
    }

    public function testPassword($username, $password)
    {
        if (!$this->bindAnonimously()){
            return;
        }
        // prepare data
        $dn = "uid=" . $username . ",ou=Users," . $this->baseDn;
        $attr = "password";

        // compare value
        $r = ldap_compare($this->connection, $dn, $attr, $password);

        if ($r === -1) {
            $this->ldapError();
            return false;
        }

        return $r;
    }

    /**
     * Search an LDAP server
     */
    public function search($basedn, $filter, $attributes)
    {
        $results = ldap_search($this->connection, $basedn, $filter, $attributes);
        if ($results)
        {
            $entries = ldap_get_entries($this->connection, $results);
            return $entries;
        }
    }

    /**
     * Add a new contact
     */
    public function add($basedn, User $user)
    {
        //set up our entry array
        $contact = array();
        $contact['objecttype'][0] = 'top';
        $contact['objectclass'][1] = 'person';
        $contact['objectclass'][2] = 'organizationalPerson';
        $contact['objectclass'][3] = 'contact';

        //add our data
        $contact['givenname'] = $user->getFirstname();
        $contact['sn'] = $user->getLastname();
        $contact['streetaddress'] = $user->getAddress();
        $contact['telephonenumber'] = $user->getPhone();

        //Create the CN entry
        $cn = 'cn='. $user->getFirstname() .' '. $user->getLastname();

        //create the DN for the entry
        $dn = 'cn='. $contact['cn'] .','. $basedn;

        //add the entry
        $result = ldap_add($this->connection, $dn, $contact);
        if (!result)
        {
            //the add failed, lets raise an error and hopefully find out why
            $this->ldapError();
        }
    }

    /**
     * Modify an existing contact
     */
    public function modify($basedn, $dnToEdit, User $user)
    {
        $usernameToEdit = $user->getUsername();
        //get a reference to the current entry

        $result = ldap_search($this->connection, $dnToEdit, "uid=$usernameToEdit");

        if (!$result) {
            // the search failed
            $this->ldapError();
        }

        //convert the results to an array for easier use.
        $contact = $this->resultToArray($result);

        //set the new values
        $contact['givenname'] = $user->getFirstname();
        $contact['sn'] = $user->getLastname();
        $contact['streetaddress'] = $user->getAddress();
        $contact['telephonenumber'] = $user->getPhone();

        //remove any empty entries
        foreach ($contact as $key => $value) {
            if (empty($value)) {
                unset($contact[$key]);
            }
        }

        //Find the new CN - in case the first or last name has changed
        $cn = 'cn='. $user->getFirstname() .' '. $user->getLastname();

        //rename the record (handling if the first/last name have changed)
        $changed = ldap_rename($this->connection, $dnToEdit, $cn, null, true);
        if ($changed)
        {
            //find the DN for the potentially revised name
            $newdn = $cn .','. $basedn;

            //now we can apply any changes in the contact information
            ldap_mod_replace($this->connection, $newdn, $contact);
        }
        else
        {
            $this->ldapError();
        }
    }

    /**
     * Remove an existing contact
     */
    public function delete($dnToDelete)
    {
        $removed = ldap_delete($this->connection, $dnToDelete);
        if (!$removed)
        {
            $this->ldapError();
        }
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
}
