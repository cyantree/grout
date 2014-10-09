<?php
namespace Cyantree\Grout\Database\Entity;

class EntityType
{
    /** @var EntityTable[] */
    public $tables = array();

    public $elementClass;

    private $queries = array();
    private $selectPlaceholderCache = array();

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
        if (is_string($idOrTable)) {
            unset($this->tables[$idOrTable]);

        } else {
            unset($this->tables[$idOrTable->id]);
        }
    }

    public function getTable($id)
    {
        return $this->tables[$id];
    }

    public function duplicate()
    {
        $t = new EntityType($this->elementClass);

        foreach ($this->tables as $key => $table) {
            $t->tables[$key] = $table->duplicate();
        }

        return $t;
    }

    public function getInsertQueries()
    {
        if (!isset($this->queries['insert'])) {
            $this->updateInsertQueries();
        }

        return $this->queries['insert'];
    }

    public function getDeleteQueries()
    {
        if (!isset($this->queries['delete'])) {
            $this->updateDeleteQueries();
        }

        return $this->queries['delete'];
    }

    public function getUpdateQueries()
    {
        if (!isset($this->queries['update'])) {
            $this->updateUpdateQueries();
        }

        return $this->queries['update'];
    }

    public function getSelectQuery()
    {
        if (!isset($this->queries['select'])) {
            $this->updateSelectQuery();
        }

        return $this->queries['select'];
    }

    public function getWhereSelectQuery()
    {
        if (!isset($this->queries['whereSelect'])) {
            $this->updateSelectQuery();
        }

        return $this->queries['whereSelect'];
    }

    public function getSelectPlaceholderCache()
    {
        if (!isset($this->queries['select'])) {
            $this->updateSelectQuery();
        }

        return $this->selectPlaceholderCache;
    }

    private function updateSelectQuery()
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
        $this->selectPlaceholderCache = array();

        $isFirstSelectedField = true;
        foreach ($this->tables as $table) {
            $tableID = $tableIDs[$table->table];

            foreach ($table->fields as $field) {
                if ($isFirstSelectedField) {
                    $isFirstSelectedField = false;

                } else {
                    $selects .= ',';
                }
                $selects .= $tableID . '.' . $field->tableField;

                $this->selectPlaceholderCache['[' . $field->id . ']'] = $tableID . '.' . $field->tableField;
            }
        }

        /* TODO: Create JOIN statements */

        $from = $primaryTable->table . ' ' . $tableIDs[$primaryTable->table];

        $where = $tableIDs[$primaryTable->table] . '.' . $primaryTable->primaryField->tableField . ' IN (%T%)';

        $query = 'SELECT ' . $selects . ' FROM ' . $from . ' ';

        $this->queries['whereSelect'] = $query;
        $this->queries['select'] = $query . 'WHERE ' . $where;
    }

    private function updateInsertQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $query = 'INSERT INTO ' . $table->table;
            $firstField = true;
            $fields = '';
            $values = '';

            foreach ($table->fields as $field) {
                if ($field->ignoreOnInsert) {
                    continue;
                }

                if ($firstField) {
                    $firstField = false;

                } else {
                    $fields .= ',';
                    $values .= ',';
                }

                $fields .= '`' . $field->tableField . '`';
                $values .= '%' . $field->queryType . ':' . $field->id . '%';
            }

            $query .= '(' . $fields . ')VALUES(' . $values . ')';

            $queries[$table->id] = $query;
        }

        $this->queries['insert'] = $queries;
    }

    private function updateUpdateQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $query = 'UPDATE ' . $table->table . ' SET ';
            $firstField = true;

            foreach ($table->fields as $field) {
                if ($field->isKey) {
                    continue;
                }

                if ($firstField) {
                    $firstField = false;

                } else {
                    $query .= ',';
                }

                $query .= '`' . $field->tableField . '`=%' . $field->queryType . ':' . $field->id . '%';
            }

            $query .= ' WHERE `' . $table->primaryField->tableField . '`=%' . $table->primaryField->queryType . ':' . $table->primaryField->id . '%';

            $queries[] = $query;
        }

        $this->queries['update'] = $queries;
    }

    private function updateDeleteQueries()
    {
        $queries = array();

        foreach ($this->tables as $table) {
            $queries[] = 'DELETE FROM ' . $table->table . ' WHERE `' . $table->primaryField->tableField . '` IN (%T%)';
        }

        $this->queries['delete'] = $queries;
    }
}
