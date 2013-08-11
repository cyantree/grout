<?php
namespace Cyantree\Grout\Database\Entity;

class EntityType
{
    /** @var EntityTable[] */
    public $tables = array();

    public $elementClass;

    private $_queries = array();
    private $_selectPlaceholderCache = array();

    public function __construct($elementClass)
    {
        $this->elementClass = $elementClass;
    }

    public function getField($id)
    {
        foreach ($this->tables as $table) {
            foreach ($table->fields as $field) {
                if ($field->id == $id) {
                    return $field;
                }
            }
        }

        return null;
    }

    public function addTable($id, $table)
    {
        $t = new EntityTable($id, $table);
        $this->tables[$id] = $t;

        return $t;
    }

    public function removeTable($idOrTable)
    {
        if (is_string($idOrTable)) unset($this->tables[$idOrTable]);
        else unset($this->tables[$idOrTable->id]);
    }

    public function getTable($id)
    {
        return $this->tables[$id];
    }

    public function duplicate()
    {
        $t = new EntityType($this->elementClass);

        foreach ($this->tables as $key => $table)
            $t->tables[$key] = $table->duplicate();

        return $t;
    }

    public function getInsertQueries()
    {
        if (!isset($this->_queries['insert'])) $this->_updateInsertQueries();
        return $this->_queries['insert'];
    }

    public function getDeleteQueries()
    {
        if (!isset($this->_queries['delete'])) $this->_updateDeleteQueries();
        return $this->_queries['delete'];
    }

    public function getUpdateQueries()
    {
        if (!isset($this->_queries['update'])) $this->_updateUpdateQueries();
        return $this->_queries['update'];
    }

    public function getSelectQuery()
    {
        if (!isset($this->_queries['select'])) $this->_updateSelectQuery();
        return $this->_queries['select'];
    }

    public function getWhereSelectQuery()
    {
        if (!isset($this->_queries['whereSelect'])) $this->_updateSelectQuery();
        return $this->_queries['whereSelect'];
    }

    public function getSelectPlaceholderCache()
    {
        if (!isset($this->_queries['select'])) $this->_updateSelectQuery();
        return $this->_selectPlaceholderCache;
    }

    private function _updateSelectQuery()
    {
        // Prepare select query
        $tableCount = 1;
        $tableIDs = array();

        $primaryTable = $this->getTable('primary');

        // Create table IDs
        foreach ($this->tables as $table) {
            $tableIDs[$table->table] = 't' . $tableCount;
            $tableCount++;
        }

        // Create query
        $selects = '';
        $this->_selectPlaceholderCache = array();

        $isFirstSelectedField = true;
        foreach ($this->tables as $table) {
            $tableID = $tableIDs[$table->table];

            foreach ($table->fields as $field) {
                if ($isFirstSelectedField) $isFirstSelectedField = false;
                else $selects .= ',';
                $selects .= $tableID . '.' . $field->tableField;

                $this->_selectPlaceholderCache['[' . $field->id . ']'] = $tableID . '.' . $field->tableField;
            }
        }

        /* TODO: Create JOIN statements */

        $from = $primaryTable->table . ' ' . $tableIDs[$primaryTable->table];

        $where = $tableIDs[$primaryTable->table] . '.' . $primaryTable->primaryField->tableField . ' IN (%T%)';

        $query = 'SELECT ' . $selects . ' FROM ' . $from . ' ';

        $this->_queries['whereSelect'] = $query;
        $this->_queries['select'] = $query . 'WHERE ' . $where;
    }

    private function _updateInsertQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $query = 'INSERT INTO ' . $table->table;
            $firstField = true;
            $fields = '';
            $values = '';

            foreach ($table->fields as $field) {
                if ($field->ignoreOnInsert) continue;

                if ($firstField) $firstField = false;
                else {
                    $fields .= ',';
                    $values .= ',';
                }

                $fields .= '`' . $field->tableField . '`';
                $values .= '%' . $field->queryType . ':' . $field->id . '%';
            }

            $query .= '(' . $fields . ')VALUES(' . $values . ')';

            $queries[$table->id] = $query;
        }

        $this->_queries['insert'] = $queries;
    }

    private function _updateUpdateQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $query = 'UPDATE ' . $table->table . ' SET ';
            $firstField = true;

            foreach ($table->fields as $field) {
                if ($field->isKey) continue;

                if ($firstField) $firstField = false;
                else $query .= ',';

                $query .= '`' . $field->tableField . '`=%' . $field->queryType . ':' . $field->id . '%';
            }

            $query .= ' WHERE `' . $table->primaryField->tableField . '`=%' . $table->primaryField->queryType . ':' . $table->primaryField->id . '%';;

            $queries[] = $query;
        }

        $this->_queries['update'] = $queries;
    }

    private function _updateDeleteQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $queries[] = 'DELETE FROM ' . $table->table . ' WHERE `' . $table->primaryField->tableField . '` IN (%T%)';
        }

        $this->_queries['delete'] = $queries;
    }
}