<?php
namespace Cyantree\Grout\Database;

class DatabaseUpdateQuery
{
    public $table;

    private $fields = array();

    private $where;

    public function __construct($table = '')
    {
        $this->table = $table;
    }

    public function set($field, $value, $type = '%t%')
    {
        $this->fields[] = array($field, $value, $type);
    }

    public function where($field, $value, $type = '%t%')
    {
        $this->where = array($field, $value, $type);
    }

    public function getQuery(Database $databaseConnection)
    {
        if (count($this->fields) == 0) {
            return '';
        }

        $q = 'UPDATE ' . $this->table . ' SET ';

        $first = true;
        $data = array();
        foreach ($this->fields as $field) {
            if ($first) {
                $first = false;
            } else {
                $q .= ',';
            }
            $q .= $field[0] . '=' . $field[2];
            $data[] = $field[1];
        }

        $q .= ' WHERE ' . $this->where[0];
        $data[] = $this->where[1];

        return $databaseConnection->prepareQuery($q, $data);
    }
}
