<?php
namespace Cyantree\Grout\Database\Entity\Fields;

use Cyantree\Grout\Database\Entity\EntityField;
use Cyantree\Grout\DateTime\DateTime;

class TimestampLocalField extends EntityField
{
    public $queryType = 'r';

    public function encodeForQuery($value)
    {
        return $value === null ? '"0000-00-00 00:00:00"' : '"' . DateTime::$local->toSqlString($value) . '"';
    }

    public function decodeFromQuery($value)
    {
        DateTime::$local->setBySqlString($value);
        return DateTime::$local->getTimestamp();
    }
}