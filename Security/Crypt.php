<?php
namespace Cyantree\Grout\Security;

class Crypt
{
    public $algorithm = MCRYPT_RIJNDAEL_256;
    public $algorithmDirectory = '';
    public $encryptionMode = MCRYPT_MODE_CBC;
    public $encryptionModeDirectory = '';
    public $randomSource = MCRYPT_RAND;

    private $key;
    private $keyLength;
    private $iv;
    private $ivLength;

    private $crypt;

    public function init()
    {
        $this->crypt = mcrypt_module_open($this->algorithm, $this->algorithmDirectory, $this->encryptionMode, $this->encryptionModeDirectory);
        $this->keyLength = mcrypt_enc_get_key_size($this->crypt);
        $this->ivLength = mcrypt_enc_get_iv_size($this->crypt);
    }

    public function deinit()
    {
        if ($this->crypt === null) {
            mcrypt_module_close($this->crypt);
            $this->crypt = null;
            $this->key = $this->iv = $this->keyLength = $this->ivLength = null;
        }
    }

    public function getKeyLength()
    {
        return $this->keyLength;
    }

    public function getIvLength()
    {
        return $this->ivLength;
    }

    public function createIv()
    {
        $this->iv = mcrypt_create_iv($this->ivLength, $this->randomSource);
    }

    public function createKey()
    {
        $this->key = $this->createRandomString($this->keyLength);
    }

    public function setKey($key, $base64Encoded = false)
    {
        $this->key = null;

        if ($base64Encoded) {
            $key = base64_decode($key);
        }

        if (strlen($key) != $this->keyLength) {
            return false;
//            trigger_error('Key does not have required length ' . $this->_keyLength);

        } else {
            $this->key = $key;

            return true;
        }
    }

    public function getKey($base64Encoded = false)
    {
        return $base64Encoded ? base64_encode($this->key) : $this->key;
    }

    public function setIv($iv, $base64Encoded = false)
    {
        $this->iv = null;

        if ($base64Encoded) {
            $iv = base64_decode($iv);
        }

        if (strlen($iv) != $this->ivLength) {
            return false;
//            trigger_error('IV does not have required length ' . $this->_ivLength);

        } else {
            $this->iv = $iv;
            return true;
        }
    }

    public function getIv($base64Encoded = false)
    {
        return $base64Encoded ? base64_encode($this->iv) : $this->iv;
    }

    public function encrypt($text, $base64Encoded = false)
    {
        if ($text == '' || $text === null) {
            return null;
        }

        if ($this->key === null) {
            trigger_error('Key is not set');
            return false;
        }

        if ($this->iv === null) {
            trigger_error('IV is not set');
            return false;
        }

        mcrypt_generic_init($this->crypt, $this->key, $this->iv);
        $out = mcrypt_generic($this->crypt, $text);
        mcrypt_generic_deinit($this->crypt);
        return $base64Encoded ? base64_encode($out) : $out;
    }

    public function decrypt($text, $base64Encoded = null)
    {
        if ($text == '' || $text === null) {
            return null;
        }

        if ($this->key === null) {
            trigger_error('Key is not set');
            return false;
        }

        if ($this->iv === null) {
            trigger_error('IV is not set');
            return false;
        }

        mcrypt_generic_init($this->crypt, $this->key, $this->iv);
        $out = trim(mdecrypt_generic($this->crypt, $base64Encoded ? base64_decode($text) : $text), chr(0));
        mcrypt_generic_deinit($this->crypt);
        return $out;
    }

    private function createRandomString($length)
    {
        $s = '';
        for ($i = 0; $i < $length; $i++) {
            $s .= chr(mt_rand(0, 255));
        }

        return $s;
    }

    public function __wakeup()
    {
        $this->crypt = mcrypt_module_open($this->algorithm, $this->algorithmDirectory, $this->encryptionMode, $this->encryptionModeDirectory);
    }
}
