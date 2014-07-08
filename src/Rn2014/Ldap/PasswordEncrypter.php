<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 03:54
 */

namespace Rn2014\Ldap;

class PasswordEncrypter
{
    public function sshaEncrypt($password) {
        $salt = sha1(rand());
        $salt = substr($salt, 0, 4);
        $hash = base64_encode( sha1($password . $salt, true) . $salt );
        return $hash;
    }

    public function createSambaPasswords($password) {
        $MKNTPWD = "/usr/local/sbin/mkntpwd";
        $SAMBANTATTR = "sambaNTPassword";
        $SAMBALMATTR = "sambaLMPassword";
        $sambaPass = array(
            "sambaLMPassword" => NULL,
            "sambaNTPassword" => NULL
        );

        if (!(@file_exists($MKNTPWD) && is_executable($MKNTPWD))) {
            throw new \Exception("You don't have the mkntpwd program in the correct path (look in config.php)
            or it is not executable");
        }
        $sambaPassCommand = $MKNTPWD . " " . $password;
        if ($sambaPassCommandOutput = shell_exec($sambaPassCommand)) {
            $sambaPass[$SAMBALMATTR] = trim(substr($sambaPassCommandOutput, 0, strPos($sambaPassCommandOutput, ':')));
            $sambaPass[$SAMBANTATTR] = trim(substr($sambaPassCommandOutput, strPos($sambaPassCommandOutput, ':') +1));
        } else {
            fatal_error("The mkntpwd has failed making the NTHashes for Samba");
        }
        return $sambaPass;
    }
}