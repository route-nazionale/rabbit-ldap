<?php
/**
 * User: lancio
 * Date: 05/06/14
 * Time: 22:33
 */

namespace Rn2014;

class AESEncoder
{
    private $publicKey;
    private $iv;

    public function __construct($publicKey, $iv)
    {
        $this->publicKey = $publicKey;
        $this->iv = $iv;
        $this->cipher = new \Crypt_AES(CRYPT_AES_MODE_CBC);
    }

    public function decode($testo)
    {
        $crypt_text = base64_decode($testo);

        $this->cipher->setKey($this->publicKey);
        $this->cipher->setIV($this->iv);

        $this->cipher->disablePadding();

        $decoded = $this->cipher->decrypt($crypt_text);

        $decoded  = $this->unpad($decoded, 32);

        return $decoded;
    }

    public function encode($testo)
    {
        $this->cipher->setKey($this->publicKey);
        $this->cipher->setIV($this->iv);

//        $testo  = $this->pad($testo, 32);

        $encoded = $this->cipher->encrypt($testo);

        $crypt_text = base64_encode($encoded);

        return $crypt_text;
    }

    public function pad ( $data, $block_size ) {

        $pad = $block_size - ( strlen( $data ) % $block_size );

        return $data . str_repeat( chr( $pad ), $pad );

    }

    public function unpad ( $data, $block_size ) {

        $pad = ord( substr( $data, -1 ) );

        if ( $pad > $block_size ) {
            return false;
        }

        if ( $pad === strspn( $data, chr( $pad ), -$pad ) ) {
            return substr( $data, 0, -1 * $pad );
        }
        else {
            return false;
        }

    }

}
