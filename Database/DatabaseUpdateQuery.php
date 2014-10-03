<?php
namespace Cyantree\Grout\Database;

class DatabaseUpdateQuery
{
    public $table;

    private $_fields = array();

    private $_where;

    public function __construct($table = '')
    {
        $this->table = $table;
    }

    public function set($field, $value, $type = '%t%')
    {
        $this->_fields[] = array($field, $value, $type);
    }

    public function where($field, $value, $type = '%t%')
    {
        $this->_where = array($field, $value, $type);
    }

    public function getQuery(Database $databaseConnection)
    {
        if (count($this->_fields) == 0) {
            return '';
        }

        $q = 'UPDATE ' . $this->table . ' SET ';

        $first = true;
        $data = array();
        foreach ($this->_fields as $field) {
            if ($first) {
                $first = false;
            } else {
                $q .= ',';
            }
            $q .= $field[0] . '=' . $field[2];
            $data[] = $field[1];
        }

        $q .= ' WHERE ' . $this->_where[0];
        $data[] = $this->_where[1];

        return $databaseConnection->prepareQuery($q, $data);
    }
}