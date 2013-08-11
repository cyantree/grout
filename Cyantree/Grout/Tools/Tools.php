<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\DateTime\DateTime;

class Tools
{
    public static function init()
    {
        DateTime::$default = new DateTime();
        DateTime::$local = new DateTime();
        DateTime::$utc = new DateTime('now', new \DateTimeZone('utc'));
    }
}