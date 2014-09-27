<?php
namespace Cyantree\Grout\Database;

class DatabaseInsertQuery
{
    public $table;
    public $fields = array();
    public $values = array();

    public function __construct($table = '')
    {
        $this->table = $table;
    }

    public function getQuery(Database $databaseConnection)
    {
        if (count($this->fields) == 0) {
            return '';
        }

        $q = 'INSERT INTO ' . $this->table;

        $fields = '';
        $values = '';
        $data = array();

        $first = true;
        foreach ($this->fields as $field) {
            if ($first) {
                $first = false;
            } else {
                $fields .= ',';
                $values .= ',';
            }
            $fields .= $field[0];
            $values .= $field[2];
            $data[] = $field[1];
        }

        return $databaseConnection->prepareQuery($q . '(' . $fields . ')VALUES(' . $values . ')', $data);
    }
}