<?php
namespace Cyantree\Grout\Database\Entity;

use Cyantree\Grout\Database\Database;

class Entity
{
    /** @var EntityType */
    public $elementType;

    public $isNew = true;

    private static $templates = array();

    /** @var EntityType[] */
    public static $defaultTypes = array();

    public function __construct()
    {
    }

    /** @return EntityType */
    public function getType()
    {
        if (!$this->elementType) {
            $this->elementType = self::getDefaultType(get_class($this));
        }

        return $this->elementType;
    }

    /** @return EntityType */
    public static function getDefaultType($class)
    {
        if (isset(self::$defaultTypes[$class])) {
            return self::$defaultTypes[$class];
        }

        class_exists($class);

        self::$defaultTypes[$class] = call_user_func(array($class, 'createDefaultType'));
        return self::$defaultTypes[$class];
    }

    public static function setDefaultType($class, $type)
    {
        self::$defaultTypes[$class] = $type;
    }

    public function getDatabase()
    {
        return Database::getDefault();
    }

    public static function deleteItems($items)
    {
        if (!$items) {
            return;
        }

        if (!is_array($items)) {
            $items = array($items);
        }

        /** @var $i Entity */
        $i = current($items);

        $type = $i->getType();
        $database = $i->getDatabase();

        $queries = $type->getDeleteQueries();

        $deleteIDs = array();

        $primaryField = $type->getTable('primary')->primaryField;
        $primaryFieldID = $primaryField->id;

        foreach ($items as $item) {
            $deleteIDs[] = $item->{$primaryFieldID};
        }

        foreach ($queries as $query) {
            $database->exec($query, array($deleteIDs));
        }
    }

    /** @param $type EntityType|string
     * @return Entity[]|Entity|null
     */
    public static function loadById($type, $id)
    {
        if (is_string($type)) {
            $type = self::getDefaultType($type);
        }

        /** @var EntityType $type */

        $singleItem = !is_array($id);

        if ($singleItem) {
            $id = array($id);
        }

        // Get helper variables
        $template = self::getTemplate($type);
        $selectQuery = $type->getSelectQuery();
        $primaryField = $type->getTable('primary')->primaryField;
        $primaryFieldName = $primaryField->id;
        $elementClass = $type->elementClass;
        $database = $template->getDatabase();

        // Fetch data
        $data = $database->query($selectQuery, array($id));

        $temp = array();
        $item = null;

        // Read data and populate temporary array
        while ($d = $data->read()) {
            /** @var $item Entity */
            $item = new $elementClass();
            $item->isNew = false;
            $item->elementType = $type;

            foreach ($type->tables as $table) {
                foreach ($table->fields as $field) {
                    $item->{$field->id} = $field->decodeFromQuery($d[$field->tableField]);
                }
            }

            $temp[$item->{$primaryFieldName}] = $item;
        }

        if ($singleItem && !isset($temp[$id[0]])) {
            return null;
        }

        // Reorder data and insert not existing elements
        $results = array();
        foreach ($id as $i) {
            if (isset($temp[$i])) {
                $results[$i] = $temp[$i];
            } else {
                $item = new $elementClass();
                $item->elementType = $type;
                $item->{$primaryFieldName} = $i;
                $results[$i] = $item;
            }
        }

        if ($singleItem) {
            return $results[$id[0]];

        } else {
            return $results;
        }
    }

    /** @return Entity|null */
    public static function loadSingleByQuery($type, $whereClause, $args = null)
    {
        $res = self::loadByQuery($type, $whereClause . ' LIMIT 0,1', $args);
        if (!count($res)) {
            return null;
        }

        return array_shift($res);
    }

    /** @param $type Entity|string */
    public static function loadByQuery($type, $whereClause = null, $args = null)
    {
        if (is_string($type)) {
            $type = self::getDefaultType($type);
        }

        /** @var EntityType $type */

        // Get helper variables
        $template = self::getTemplate($type);
        $selectQuery = $type->getWhereSelectQuery();
        $primaryField = $type->getTable('primary')->primaryField;
        $primaryFieldName = $primaryField->id;
        $elementClass = $type->elementClass;
        $database = $template->getDatabase();

        // Fetch data
        $cache = $type->getSelectPlaceholderCache();
        $whereClause = str_replace(array_keys($cache), array_values($cache), $whereClause);
        $data = $database->query($selectQuery . $whereClause, $args);

        $results = array();
        $item = null;

        // Read data and populate temporary array
        while ($d = $data->read()) {
            /** @var $item Entity */
            $item = new $elementClass();
            $item->isNew = false;
            $item->elementType = $type;

            foreach ($type->tables as $table) {
                foreach ($table->fields as $field) {
                    $item->{$field->id} = $field->decodeFromQuery($d[$field->tableField]);
                }
            }

            $results[$item->{$primaryFieldName}] = $item;
        }

        return $results;
    }

    public function save($saveMode = null)
    {
        $type = $this->getType();

        $database = $this->getDatabase();

        $primaryFieldName = $type->getTable('primary')->primaryField->id;

        $data = self::preparePropertiesForQuery($this);

        // Insert
        if ($saveMode == 'insert' || ($saveMode != 'update' && $this->{$primaryFieldName} === null)) {
            $queries = $type->getInsertQueries();

            foreach ($queries as $tableID => $query) {
                $result = $database->exec($query, $data);
                if ($tableID == 'primary' && $this->{$primaryFieldName} === null) {
                    $this->{$primaryFieldName} = $result;
                }
            }

            $this->isNew = false;

            // Update
        } else {
            $queries = $type->getUpdateQueries();

            foreach ($queries as $query) {
                $database->exec($query, $data);
            }
        }
    }

    /** @param $item Entity */
    private static function preparePropertiesForQuery($item)
    {
        $type = $item->getType();

        $prop = array();

        foreach ($type->tables as $table) {
            foreach ($table->fields as $field) {
                $prop[$field->id] = $field->encodeForQuery($item->{$field->id});
            }
        }

        return $prop;
    }

    /** @return Entity */
    private static function getTemplate($type)
    {
        $typeClass = get_class($type);
        if (isset(self::$templates[$typeClass])) {
            return self::$templates[$typeClass];
        }

        $className = $type->elementClass;

        $t = new $className();
        $t->elementType = $type;

        self::$templates[$typeClass] = $t;

        return $t;
    }

    public function delete()
    {
        self::deleteItems($this);
    }
}
