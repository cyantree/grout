<?php
namespace Cyantree\Grout\Tools;

class NetTools
{
    public static function ipToNumber($ip)
    {
        $ip = explode('.', $ip);

        return (($ip[0] << 24) + ($ip[1] << 16) + ($ip[2] << 8) + $ip[3]);
    }

    public static function numberToIpP($number)
    {
        return ($number >> 24 & 0xFF) . '.' . ($number >> 16 & 0xFF) . '.' . ($number >> 8 & 0xFF) . '.' . ($number & 0xFF);
    }
}