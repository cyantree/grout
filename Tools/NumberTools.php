<?php
namespace Cyantree\Grout\Tools;

class NumberTools
{
    /**
     * @param float $number
     * @param float $min
     * @param float $max
     * @return float
     */
    public static function limit($number, $min = null, $max = null)
    {
        if ($min !== null && $number < $min) {
            return $min;
        }
        if ($max !== null && $number > $max) {
            return $max;
        }

        return $number;
    }

    public static function interpolate($factor, $min, $max)
    {
        return $min + ($max - $min) * $factor;
    }

    public static function getFactor($value, $min, $max)
    {
        return ($value - $min) / ($max - $min);
    }

    /**
     * @param float $deg
     * @return float
     */
    public static function deg2Rad($deg)
    {
        return $deg / 180 * M_PI;
    }

    /**
     * @param float $rad
     * @return float
     */
    public static function rad2Deg($rad)
    {
        return $rad / M_PI * 180;
    }
}
