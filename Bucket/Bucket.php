<?php
namespace Cyantree\Grout\Bucket;

use Cyantree\Grout\Tools\StringTools;

class Bucket
{
    public static $defaultExpiration = 86400;

    public $id;
    public $context;
    public $data;
    public $expires;

    /** @return \Cyantree\Grout\Bucket\Bucket */
    public function create($data = '', $expires = null, $context = null, $id = null, $returnNewBucket = true)
    {
    }

    /** @return \Cyantree\Grout\Bucket\Bucket|bool */
    public function load($id, $context = null, $returnNewBucket = true)
    {
    }

    public function save()
    {
    }

    public function delete()
    {
    }

    public function cleanUp()
    {
    }

    public static function mapExpirationDate($expires)
    {
        if (is_string($expires)) {
            return strtotime($expires);
        }

        if (!$expires) {
            $expires = self::$defaultExpiration;
        }

        if ($expires < 1000000000) {
            return time() + $expires;
        }

        return $expires;
    }

    public static function isValidId($id)
    {
        return preg_match('/^[a-zA-Z0-9._-]{1,32}$/', $id);
    }

    public static function createId(){
        return StringTools::random(32);
    }
}