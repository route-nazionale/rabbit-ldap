<?php
/**
 * User: lancio
 * Date: 18/07/14
 * Time: 01:21
 */
namespace Rn2014\Auth;

interface AuthInterface
{
    public function testLogin($username, $password);

    public function attemptLogin($username, $decodedPassword, $group);

    public function attemptLoginWithBirthdate($username, $decodedBirthdate, $group);
}