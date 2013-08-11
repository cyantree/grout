<?php
namespace Cyantree\Grout\DateTime\Languages;

use Cyantree\Grout\DateTime\DateTimeLanguage;

class DateTimeLanguageEn extends DateTimeLanguage
{
    public $formatLongDate = 'j. F Y';
    public $formatLongDateTime = 'j. F Y G:i';
    public $formatLongDateTimeSeconds = 'j. F Y G:i:s';

    public $formatTime = 'G:i';
    public $formatTimeSeconds = 'G:i:s';

    public $monthsShort = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
    public $monthsLong = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

    public $daysShort = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
    public $daysLong = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
}