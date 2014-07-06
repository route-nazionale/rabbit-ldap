<?php
/**
 * User: lancio
 * Date: 04/07/14
 * Time: 22:40
 */

namespace Rn2014\Ldap;

use Toyota\Component\Ldap\Core\Manager;
use Toyota\Component\Ldap\Platform\Native\Driver;
use Toyota\Component\Ldap\Platform\Test\Search;

/**
 * @deprecated
 * Class LdapCaller
 * @package Rn2014\Ldap
 */
class LdapCaller {

    private $ldap;
    private $baseDn;

    public function __construct($params)
    {
        $this->baseDn = $params["base_dn"];
        $this->manager = new Manager($params, new Driver());

        $this->manager->connect();
    }

    public function getDn($username)
    {
        $results = $this->manager->search("ou=Users,".$this->baseDn, "uid=$username", true);

        $dn = false;

        foreach ($results as $node) {
            $dn = $node->getDn();
        }
        if (!$dn) {
            throw new \Exception("User [{$username}] not Found");
        }

        return $dn;
    }

    public function isUserInGroup($group, $username)
    {
        $results = $this->manager->search("ou=Groups,".$this->baseDn, '(&(cn='.$group.')(memberUid='.$username.'))', true);

        var_dump($results->next() );
    }

    public function bindAnonimously()
    {
        $this->manager->bind();
    }

    public function login($dn , $password)
    {
        $this->bind($dn , $password);
    }

    public function bind($dn , $password)
    {
        $this->manager->bind($dn, $password);
    }

    public function getUser($dn)
    {
        $node = $this->manager->getNode($dn);
        return $node;
    }

    public function getUserDetail($dn, $detail = "person")
    {
        $node = $this->manager->search($dn,"(&(cn=%)(objectclass=$detail))",true);
        return $node;
    }

    public function newUser($dn)
    {
        $node = new Node();
        $node->setDn($dn);
        $node->get('objectClass', true)->add(array('top', 'organizationalUnit'));
        // The true param creates the attribute on the fly
        $node->get('ou', true)->set('Users');

        return $this->manager->save($node);
    }

    public function save($dn, $password, $params)
    {
        $this->bind($dn, $password);

        $node = $this->manager->getNode($dn);
        if (!count($params)) {
            return false;
        }
        foreach ($params as $key => $val) {
            $field = $node->get($key);
            if ($field) {
                $field->set($val);
            }
        }

        return $this->manager->save($node);
    }
}
