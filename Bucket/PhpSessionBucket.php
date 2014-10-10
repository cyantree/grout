<?php
namespace Cyantree\Grout\Bucket;

use Cyantree\Grout\Bucket\Bucket;

class PhpSessionBucket extends Bucket
{
    public $containerName = 'CT_SessionBuckets';

    public function save()
    {
        if ($this->id === null) {
            $this->id = $this->createBucketId();
            $_SESSION[$this->containerName][$this->id] = $this;
        }

        $this->expires = Bucket::mapExpirationDate($this->expires);
    }

    public function delete()
    {
        if (!isset($_SESSION[$this->containerName])) {
            return;
        }

        if (!isset($_SESSION[$this->containerName][$this->id])) {
            return;
        }

        unset($_SESSION[$this->containerName][$this->id]);
    }

    private function mergeSettings($base)
    {
        $this->containerName = $base->containerName;
    }

    /* Work as static function */

    protected function createBucketId()
    {
        do {
            $id = Bucket::createId();
            $exists = isset($_SESSION[$this->containerName][$id]);
        } while ($exists);

        return $id;
    }

    public function cleanUp()
    {
        if (!isset($_SESSION[$this->containerName])) {
            return;
        }

        $t = time();

        /** @var $bucket \Cyantree\Grout\Bucket\PhpSessionBucket */
        foreach ($_SESSION[$this->containerName] as $bucket) {
            if ($bucket->expires < $t) {
                unset($_SESSION[$this->containerName][$bucket->id]);
            }
        }
    }

    private function checkContainer()
    {
        if (!isset($_SESSION[$this->containerName])) {
            $_SESSION[$this->containerName] = array();
        }
    }

    public function create($data = '', $expires = null, $context = null, $id = null, $returnNewBucket = true)
    {
        $this->checkContainer();

        if ($returnNewBucket) {
            $b = new PhpSessionBucket();
            $b->mergeSettings($this);

        } else {
            $b = $this;
        }

        $b->data = $data;
        $b->context = $context;
        $b->expires = Bucket::mapExpirationDate($expires);

        if ($id) {
            $b->id = $id;

        } else {
            $b->id = $this->createBucketId();
        }

        $_SESSION[$this->containerName][$b->id] = $b;

        return $b;
    }

    public function load($id, $context = null, $returnNewBucket = true)
    {
        if (!Bucket::isValidId($id)) {
            return false;
        }

        if (!isset($_SESSION[$this->containerName]) || !isset($_SESSION[$this->containerName][$id])) {
            return false;
        }

        /** @var $b \Cyantree\Grout\Bucket\PhpSessionBucket */
        $b = $_SESSION[$this->containerName][$id];

        if ($context !== false && $b->context != $context) {
            return false;
        }

        if (!$returnNewBucket) {
            $this->mergeSettings($b);
            $this->id = $b->id;
            $this->data = $b->data;
            $this->expires = $b->expires;
            $this->context = $b->context;

            $b = $this;
        }

        return $b;
    }
}
