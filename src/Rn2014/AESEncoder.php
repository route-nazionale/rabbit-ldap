<?php
/**
 * User: lancio
 * Date: 05/06/14
 * Time: 22:33
 */

namespace RN2014;

class AESEncoder
{
    private $publicKey;
    private $privateKey;

    public function __construct($publicKey, $privateKey = null)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->cipher = new \Crypt_AES(CRYPT_AES_MODE_CBC);
    }

    public function decode($testo)
    {
        $crypt_text = base64_decode($testo);

        $iv = substr($crypt_text,0,32);
        $testoRidotto = substr($crypt_text,32);

        $this->cipher->setKey($this->publicKey);
        $this->cipher->setIV($iv);
        $decoded = $this->cipher->decrypt($testoRidotto);

        return $decoded;
    }

    public function encode($testo)
    {
//        $crypt_text = base64_decode($testo);

//        $iv = substr($crypt_text,0,32);
//        $testoRidotto = substr($crypt_text,32);


        $this->cipher->setKey($this->privateKey);
//        $this->cipher->setIV($iv);
        $encoded = $this->cipher->encrypt($testo);
        $crypt_text = base64_encode($encoded);
        return $crypt_text;
    }
}
