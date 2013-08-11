<?php
namespace Cyantree\Grout\Database\Entity;

class EntityTable
{
    const FIELD_TYPE_STRING = 'string';
    const FIELD_TYPE_BOOLEAN = 'boolean';
    const FIELD_TYPE_AUTOINCREMENT = 'autoincrement';
    const FIELD_TYPE_TIMESTAMP_LOCAL = 'timestampLocal';
    const FIELD_TYPE_TIMESTAMP_UTC = 'timestampUTC';

    public $id;
    public $table;

    /** @var EntityField*/
    public $primaryField;

    /** @var EntityField[] */
    public $fields = array();

    public function __construct($id, $table)
    {
        $this->id = $id;
        $this->table = $table;
    }

    public function addField($id, $type = self::FIELD_TYPE_STRING, $primaryKey = false)
    {
        if ($type === null || $type == self::FIELD_TYPE_STRING) {
            $class = 'Cyantree\Grout\Database\Entity\EntityField';
        } else if ($type == self::FIELD_TYPE_BOOLEAN) {
            $class = 'Cyantree\Grout\Database\Entity\Fields\BooleanField';
        } else if ($type == self::FIELD_TYPE_TIMESTAMP_LOCAL) {
            $class = 'Cyantree\Grout\Database\Entity\Fields\TimestampLocalField';
        } else if ($type == self::FIELD_TYPE_TIMESTAMP_UTC) {
            $class = 'Cyantree\Grout\Database\Entity\Fields\TimestampUtcField';
        } else if ($type == self::FIELD_TYPE_AUTOINCREMENT) {
            $class = 'Cyantree\Grout\Database\Entity\Fields\AutoIncrementField';
            $primaryKey = true;
        } else {
            $class = $type;
        }

        if (is_array($id)) {
            foreach ($id as $i) {
                $f = new $class($i);
                $this->fields[$i] = $f;
            }

            return null;
        } else {
            $f = new $class($id);

            if ($primaryKey) {
                $f->isKey = true;
                $this->primaryField = $f;
            }

            $this->fields[$f->id] = $f;

            return $f;
        }
    }

    public function addExistingField($field, $primaryKey = false)
    {
        if (is_array($field)) {
            foreach ($field as $f) {
                $this->fields[$f->id] = $f;
            }

            return null;
        }

        if ($primaryKey) {
            $field->isKey = true;
            $this->primaryField = $field;
        }

        $this->fields[$field->id] = $field;

        return $field;
    }

    public function removeField($idOrField)
    {
        if (is_string($idOrField)) {
            unset($this->fields[$idOrField]);
        } else {
            unset($this->fields[$idOrField->id]);
        }
    }

    public function duplicate()
    {
        $t = new EntityTable($this->id, $this->table);
        foreach ($this->fields as $key => $field) {
            $t->fields[$key] = $field->duplicate();
        }

        $t->primaryField = $this->primaryField;

        return $t;
    }
}