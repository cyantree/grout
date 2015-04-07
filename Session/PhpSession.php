<?php
namespace Cyantree\Grout\Session;

use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class PhpSession extends Session
{
    public $id;
    public $useCookie = true;
    public $data;
    public $name = 'SessionID';
    public $expirationTime = 1800;
    public $isNew = false;

    public $checkIp = true;
    public $checkBrowser = true;

    public $cookiePath = '/';
    public $cookieDomain;
    public $cookieSecure;
    public $cookieHttpOnly = true;

    public function __construct()
    {
        ini_set('session.auto_start', '0');
        ini_set('session.use_cookies', '0');
        ini_set('session.use_trans_sid', '0');
    }

    public function load($id = null, $checkSession = true)
    {
        if ($id) {
            $this->id = $id;

        } elseif ($this->useCookie
            && isset($_COOKIE[$this->name])
            && preg_match('/^[0-9a-zA-Z]{32}$/', $_COOKIE[$this->name])
        ) {
            $this->id = $_COOKIE[$this->name];

        } else {
            $this->id = StringTools::random(32);
        }

        session_id($this->id);
        session_start();

        $this->isNew = !isset($_SESSION['_data']);

        if ($this->useCookie) {
            setcookie(
                $this->name,
                $this->id,
                time() + $this->expirationTime * 4,
                $this->cookiePath,
                $this->cookieDomain,
                $this->cookieSecure,
                $this->cookieHttpOnly
            );
        }

        if (!$this->isNew) {
            if ($checkSession && !$this->isValid()) {
                $this->reset();

            } else {
                $this->data = unserialize($_SESSION['_data']);
            }

        } else {
            $this->data = null;
        }
    }

    public function save($close = true)
    {
        if ($this->checkIp) {
            $_SESSION['_ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if ($this->checkBrowser) {
            $_SESSION['_browser'] = ArrayTools::get($_SERVER, 'HTTP_USER_AGENT');
        }

        $_SESSION['_lastAction'] = time();
        $_SESSION['_data'] = serialize($this->data);
        $_SESSION['_name'] = $this->name;

        if ($close) {
            session_write_close();
        }
    }

    public function reset()
    {
        $this->delete();

        $this->load();
    }

    public function delete()
    {
        $this->data = null;

        session_destroy();

        if ($this->useCookie && !headers_sent()) {
            unset($_COOKIE[$this->name]);
            setcookie(
                $this->name,
                '',
                time() - 100,
                $this->cookiePath,
                $this->cookieDomain,
                $this->cookieSecure,
                $this->cookieHttpOnly
            );
        }

        $this->isNew = true;
    }

    public function isValid()
    {
        return ArrayTools::get($_SESSION, '_name') == $this->name
        && isset($_SESSION['_lastAction'])
        && (!$this->checkIp || ArrayTools::get($_SESSION, '_ip') == $_SERVER['REMOTE_ADDR'])
        && (!$this->checkBrowser || $_SESSION['_browser'] == ArrayTools::get($_SERVER, 'HTTP_USER_AGENT'))
        && (time() - $_SESSION['_lastAction']) <= $this->expirationTime;
    }
}
