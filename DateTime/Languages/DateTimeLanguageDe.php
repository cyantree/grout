<?php
namespace Cyantree\Grout\DateTime\Languages;

use Cyantree\Grout\DateTime\DateTimeLanguage;

class DateTimeLanguageDe extends DateTimeLanguage
{
    public $formatLongDate = 'j. F Y';
    public $formatLongDateTime = 'j. F Y G:i';
    public $formatLongDateTimeSeconds = 'j. F Y G:i:s';

    public $formatTime = 'G:i';
    public $formatTimeSeconds = 'G:i:s';

    public $monthsShort = array('Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez');
    public $monthsLong = array('Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');

    public $daysShort = array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So');
    public $daysLong = array('Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag');
}
