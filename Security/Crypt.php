<?php
namespace Cyantree\Grout\Security;

class Crypt
{
    public $algorithm = MCRYPT_RIJNDAEL_256;
    public $algorithmDirectory = '';
    public $encryptionMode = MCRYPT_MODE_CBC;
    public $encryptionModeDirectory = '';
    public $randomSource = MCRYPT_RAND;

    private $_key;
    private $_keyLength;
    private $_iv;
    private $_ivLength;

    private $_crypt;

    public function init()
    {
        $this->_crypt = mcrypt_module_open($this->algorithm, $this->algorithmDirectory, $this->encryptionMode, $this->encryptionModeDirectory);
        $this->_keyLength = mcrypt_enc_get_key_size($this->_crypt);
        $this->_ivLength = mcrypt_enc_get_iv_size($this->_crypt);
    }

    public function deinit()
    {
        if ($this->_crypt === null) {
            mcrypt_module_close($this->_crypt);
            $this->_crypt = null;
            $this->_key = $this->_iv = $this->_keyLength = $this->_ivLength = null;
        }
    }

    public function getKeyLength()
    {
        return $this->_keyLength;
    }

    public function getIvLength()
    {
        return $this->_ivLength;
    }

    public function createIv()
    {
        $this->_iv = mcrypt_create_iv($this->_ivLength, $this->randomSource);
    }

    public function createKey()
    {
        $this->_key = $this->_createRandomString($this->_keyLength);
    }

    public function setKey($key, $base64Encoded = false)
    {
        $this->_key = null;

        if ($base64Encoded) {
            $key = base64_decode($key);
        }

        if (strlen($key) != $this->_keyLength) {
            return false;
//            trigger_error('Key does not have required length ' . $this->_keyLength);

        } else {
            $this->_key = $key;

            return true;
        }
    }

    public function getKey($base64Encoded = false)
    {
        return $base64Encoded ? base64_encode($this->_key) : $this->_key;
    }

    public function setIv($iv, $base64Encoded = false)
    {
        $this->_iv = null;

        if ($base64Encoded) {
            $iv = base64_decode($iv);
        }

        if (strlen($iv) != $this->_ivLength) {
            return false;
//            trigger_error('IV does not have required length ' . $this->_ivLength);

        } else {
            $this->_iv = $iv;
            return true;
        }
    }

    public function getIv($base64Encoded = false)
    {
        return $base64Encoded ? base64_encode($this->_iv) : $this->_iv;
    }

    public function encrypt($text, $base64Encoded = false)
    {
        if ($text == '' || $text === null) {
            return null;
        }

        if ($this->_key === null) {
            trigger_error('Key is not set');
            return false;
        }

        if ($this->_iv === null) {
            trigger_error('IV is not set');
            return false;
        }

        mcrypt_generic_init($this->_crypt, $this->_key, $this->_iv);
        $out = mcrypt_generic($this->_crypt, $text);
        mcrypt_generic_deinit($this->_crypt);
        return $base64Encoded ? base64_encode($out) : $out;
    }

    public function decrypt($text, $base64Encoded = null)
    {
        if ($text == '' || $text === null) {
            return null;
        }

        if ($this->_key === null) {
            trigger_error('Key is not set');
            return false;
        }

        if ($this->_iv === null) {
            trigger_error('IV is not set');
            return false;
        }

        mcrypt_generic_init($this->_crypt, $this->_key, $this->_iv);
        $out = trim(mdecrypt_generic($this->_crypt, $base64Encoded ? base64_decode($text) : $text), chr(0));
        mcrypt_generic_deinit($this->_crypt);
        return $out;
    }

    private function _createRandomString($length)
    {
        $s = '';
        for ($i = 0; $i < $length; $i++)
            $s .= chr(mt_rand(0, 255));

        return $s;
    }

    function __wakeup()
    {
        $this->_crypt = mcrypt_module_open($this->algorithm, $this->algorithmDirectory, $this->encryptionMode, $this->encryptionModeDirectory);
    }
}