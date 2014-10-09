<?php
namespace Cyantree\Grout\Database\Entity;

class EntityField
{
    public $id;
    public $tableField;
    public $isKey;

    public $ignoreOnInsert;

    public $queryType = 't';

    public function __construct($id)
    {
        $this->tableField = $this->id = $id;
    }

    public function duplicate()
    {
        $class = get_class($this);

        $f = new $class($this->id, $this->tableField);

        return $f;
    }

    public function encodeForQuery($value)
    {
        return $value;
    }

    public function decodeFromQuery($value)
    {
        return $value;
    }
}
