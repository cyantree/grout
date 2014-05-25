<?php
namespace Cyantree\Grout\Session;

use Cyantree\Grout\Bucket\Bucket;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class BucketSession
{
    /** @var \Cyantree\Grout\Bucket\Bucket */
    public $bucketBase;

    /** @var \Cyantree\Grout\Bucket\Bucket */
    public $bucket;

    public $id;
    public $useCookie = true;
    public $data;
    public $name = 'SessionID';
    public $expirationTime = 1800;
    public $isNew = false;

    public $checkIp = true;
    public $checkUserAgent = true;

    public $cookiePath = '/';
    public $cookieDomain;
    public $cookieSecure;
    public $cookieHttpOnly = true;

    public $ip;
    public $userAgent;

    public function __construct()
    {
        $this->ip = ArrayTools::get($_SERVER, 'REMOTE_ADDR');
        $this->userAgent = ArrayTools::get($_SERVER, 'HTTP_USER_AGENT');
        $this->name = 'Session_'.substr(md5(__FILE__), 0, 16);
    }

    public function load($id = null, $checkSession = true)
    {
        $this->id = $this->bucket = null;

        if ($id === null && $this->useCookie && isset($_COOKIE[$this->name]) && preg_match('/^[0-9a-zA-Z]{32}$/', $_COOKIE[$this->name])) {
            $id = $_COOKIE[$this->name];
        }

        if($id){
            $this->bucket = $this->bucketBase->load($id, $this->name);
        }

        $this->isNew = !$this->bucket;

        if (!$this->isNew) {
            if ($checkSession && !$this->checkSession()) {
                $this->reset();
            } else {
                $this->data = $this->bucket->data['data'];
                $this->id = $this->bucket->id;
            }
        } else {
            $this->data = null;

            $data = $this->_getBucketData();
            $this->bucket = $this->bucketBase->create($data, $this->expirationTime, $this->name);
            $this->id = $this->bucket->id;
        }
    }

    private function _getBucketData()
    {
        $d = array();

        if ($this->checkIp) {
            $d['ip'] = $this->ip;
        }

        if ($this->checkUserAgent) {
            $d['userAgent'] = $this->userAgent;
        }

        $d['lastAction'] = time();
        $d['data'] = $this->data;
        $d['name'] = $this->name;

        return $d;
    }

    public function save()
    {
        $this->bucket->data = $this->_getBucketData();
        $this->bucket->expires = $this->expirationTime;
        $this->bucket->save();

        if ($this->useCookie && !headers_sent()) {
            setcookie($this->name, $this->id, time() + $this->expirationTime, $this->cookiePath, $this->cookieDomain, $this->cookieSecure, $this->cookieHttpOnly);
        }
    }

    public function reset()
    {
        $this->delete(true);

        $this->load();
    }

    public function delete($keepBucket = false)
    {
        $this->data = null;

        if($this->bucket && !$keepBucket){
            $this->bucket->delete();
        }

        if ($this->useCookie && !headers_sent()) {
            unset($_COOKIE[$this->name]);
            setcookie($this->name, '', time() - 100, $this->cookiePath, $this->cookieDomain, $this->cookieSecure, $this->cookieHttpOnly);
        }

        $this->isNew = true;
        $this->bucket = null;
        $this->id = null;
    }

    public function checkSession()
    {
        $f = new ArrayFilter($this->bucket->data);

        if($f->get('name') != $this->name){
            return false;
        }

        if(!$f->get('lastAction') || time() - $f->get('lastAction') > $this->expirationTime){
            return false;
        }

        if($this->checkIp && $f->get('ip') != $this->ip){
            return false;
        }

        if($this->checkUserAgent && $f->get('userAgent') != $this->userAgent){
            return false;
        }

        return true;
    }
}