<?php
namespace Cyantree\Grout\Tools;

class DateTimeTools
{
    const DATEFORMAT_SQL = 'Y-m-d H:i:s';

    public static function createSqlDateTime($time, $asUtc = false)
    {
        if ($time === null || $time === false) {
            return '0000-00-00';
        }

        if ($asUtc) {
            return gmdate(self::DATEFORMAT_SQL, $time);
        }

        return date(self::DATEFORMAT_SQL, $time);
    }
}