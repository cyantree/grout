<?php
namespace Cyantree\Grout\Database;

class Database
{
    const FILTER_ALL = 1;
    const FILTER_ROW = 2;
    const FILTER_COLUMN = 4;
    const FILTER_FIELD = 8;
    const FILTER_ARRAY = 64;

    const TYPE_ASSOC = 16;
    const TYPE_NUM = 32;

    const FLAG_IGNORE_ERRORS = 128;

    /** @var DatabaseConnection */
    public static $default;

    public static $countQueries = 0;

    public static $connectHandler = null;

    public static function getDefault()
    {
        if (!self::$default && self::$connectHandler) {
            self::$connectHandler[0]->{self::$connectHandler[1]}();
        }

        return self::$default;
    }

    /** @return DatabaseReader|string|array|void */
    public static function query($query, $args = null, $flags = 0)
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        return self::$default->query($query, $args, $flags);
    }

    /** @return int|void */
    public static function exec($query, $args = null, $flags = 0)
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        return self::$default->exec($query, $args, $flags);
    }

    public static function backupLastQuery($flag)
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        self::$default->backupLastQuery($flag);
    }

    /** @return string */
    public static function getLastQuery()
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        return self::$default->getLastQuery();
    }

    /** @return string */
    public static function prepareQuery($query, $args)
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        return self::$default->prepareQuery($query, $args);
    }

    public static function insert($table, $fields, $values, $nestedRows = false, $rowsPerInsert = 100, $returnQueries = false)
    {
        if (!self::$default && self::$connectHandler) self::$connectHandler[0]->{self::$connectHandler[1]}();

        return self::$default->insert($table, $fields, $values, $nestedRows, $rowsPerInsert, $returnQueries);
    }
}