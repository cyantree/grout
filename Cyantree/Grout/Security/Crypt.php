<?php
namespace Cyantree\Grout\Security;

class Crypt
{
    public $key;
    public $iv;

    public $useBase64Encoding = true;

    public $randomSource = MCRYPT_RAND;

    private $_crypt;

    function __construct()
    {
        $this->_crypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    }

    public function createIv()
    {
        return $this->iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->_crypt), $this->randomSource);
    }

    public function createKey()
    {
        return $this->key = $this->_createRandomString(mcrypt_enc_get_key_size($this->_crypt));
    }

    public function setKey($key, $base64Encoded = true)
    {
        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        $this->key = !$base64Encoded ? $key : base64_decode($key);
    }

    public function getKey($base64Encoded = null)
    {
        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        return $base64Encoded ? base64_encode($this->key) : $this->key;
    }

    public function setIv($iv, $base64Encoded = null)
    {
        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        $this->iv = !$base64Encoded ? $iv : base64_decode($iv);
    }

    public function getIv($base64Encoded = null)
    {
        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        return $base64Encoded ? base64_encode($this->iv) : $this->iv;
    }

    public function encrypt($text, $base64Encoded = null)
    {
        if ($text == '' || $text === null) {
            return null;
        }

        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        mcrypt_generic_init($this->_crypt, $this->key, $this->iv);
        $out = mcrypt_generic($this->_crypt, $text);
        mcrypt_generic_deinit($this->_crypt);
        return $base64Encoded ? base64_encode($out) : $out;
    }

    public function decrypt($text, $base64Encoded = null)
    {
        if ($text == '' || $text === null) return null;

        $base64Encoded = $base64Encoded !== false && ($base64Encoded || $this->useBase64Encoding);

        mcrypt_generic_init($this->_crypt, $this->key, $this->iv);
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
        $this->_crypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
    }
}