<?php
namespace Cyantree\Grout\DateTime;

use Cyantree\Grout\DateTime\Languages\DateTimeLanguageEn;
use Cyantree\Grout\Tools\DateTimeTools;

class DateTime extends \DateTime
{
    /** @var DateTimeLanguage */
    public $language;

    /** @var DateTime */
    public static $default;

    /** @var DateTime */
    public static $local;

    /** @var DateTime */
    public static $utc;

    private static $monthsShort = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    private static $monthsLong = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

    private static $daysShort = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    private static $daysLong = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

    private static $placeholderMonthsShort = array('#M01#', '#M02#', '#M03#', '#M04#', '#M05#', '#M06#', '#M07#', '#M08#', '#M09#', '#M10#', '#M11#', '#M12');
    private static $placeholderMonthsLong = array('#M01L#', '#M02L#', '#M03L#', '#M04L#', '#M05L#', '#M06L#', '#M07L#', '#M08L#', '#M09L#', '#M10L#', '#M11L#', '#M12L#');

    private static $placeholderDaysShort = array('#D01#', '#D02#', '#D03#', '#D04#', '#D05#', '#D06#', '#D07#');
    private static $placeholderDaysLong = array('#D01L#', '#D02L#', '#D03L#', '#D04L#', '#D05L#', '#D06L#', '#D07L#');

    public function __construct($time = 'now', \DateTimeZone $timezone = null, $language = null)
    {
        if (!$language) $this->language = new DateTimeLanguageEn();
        else {
            $this->language = $language;
        }

        if ($timezone === null) $timezone = new \DateTimeZone(date_default_timezone_get());

        parent::__construct($time, $timezone);
    }

    public function format($format, $timestamp = null)
    {
        if ($timestamp !== null) $this->setTimestamp($timestamp);

        $string = parent::format($format);

        if (!$this->language || get_class($this->language) == 'Cyantree\Grout\DateTime\Languages\DateTimeLanguageEn') return $string;

        $replaceMonthsLong = strpos($format, 'F') !== false;
        $replaceMonthsShort = strpos($format, 'M') !== false;
        $replaceDaysLong = strpos($format, 'l') !== false;
        $replaceDaysShort = strpos($format, 'D') !== false;

        if ($replaceMonthsLong) $string = str_replace(self::$monthsLong, self::$placeholderMonthsLong, $string);
        if ($replaceMonthsShort) $string = str_replace(self::$monthsShort, self::$placeholderMonthsShort, $string);
        if ($replaceDaysLong) $string = str_replace(self::$daysLong, self::$placeholderDaysLong, $string);
        if ($replaceDaysShort) $string = str_replace(self::$daysShort, self::$placeholderDaysShort, $string);

        if ($replaceMonthsLong) $string = str_replace(self::$placeholderMonthsLong, $this->language->monthsLong, $string);
        if ($replaceMonthsShort) $string = str_replace(self::$placeholderMonthsShort, $this->language->monthsShort, $string);
        if ($replaceDaysLong) $string = str_replace(self::$placeholderDaysLong, $this->language->daysLong, $string);
        if ($replaceDaysShort) $string = str_replace(self::$placeholderDaysShort, $this->language->daysShort, $string);

        return $string;
    }

    public function setByString($string)
    {
        $timestamp = strtotime($string);
        if ($timestamp === false) return false;

        $this->setTimestamp($timestamp - $this->getOffset());

        return true;
    }

    public function setBySqlString($sqlDateTime)
    {
        $this->setDate(substr($sqlDateTime, 0, 4), substr($sqlDateTime, 5, 2), substr($sqlDateTime, 8, 2));
        $this->setTime(substr($sqlDateTime, 11, 2), substr($sqlDateTime, 14, 2), substr($sqlDateTime, 17, 2));

        return $this;
    }

    public function toSqlString($timestamp = null)
    {
        return $this->format(DateTimeTools::DATEFORMAT_SQL, $timestamp);
    }

    public function toLongDateTimeString($timestamp = null, $showSeconds = false)
    {
        return $this->format($showSeconds ? $this->language->formatLongDateTimeSeconds : $this->language->formatLongDateTime, $timestamp);
    }

    public function toLongDateString($timestamp = null)
    {
        return $this->format($this->language->formatLongDate, $timestamp);
    }

    public function toTimeString($timestamp = null, $showSeconds = false)
    {
        return $this->format($showSeconds ? $this->language->formatTimeSeconds : $this->language->formatTime, $timestamp);
    }

    public function copy()
    {
        $d = new DateTime('now', $this->getTimezone(), $this->language);
        $d->setTimestamp($this->getTimestamp());

        return $d;
    }
}