<?php
namespace Cyantree\Grout\Database;

use Cyantree\Grout\ErrorHandler;
use Cyantree\Grout\Tools\ArrayTools;

class MySqlConnection extends DatabaseConnection
{
    public $id = 'SQL';

    private $connection;

    public $conversionTarget = 'sql';

    public function __construct()
    {
        parent::__construct();

        if (self::$filterArraySearch === null) {
            self::$filterArraySearch = array('\\', '"', "\0", "\n", "\r", chr(26), "'");
        }
    }


    public function connect($host, $user, $pass, $database, $charset = 'utf8', $newConnection = true)
    {
        if ($this->connection) {
            return true;
        }
        $this->connection = mysql_connect($host, $user, $pass, $newConnection);
        if ($this->connection === false) {
            $this->connection = null;

            return false;
        }

        $select = mysql_select_db($database, $this->connection);
        if ($select === false) {
            $this->connection = null;

            return false;
        }

        mysql_set_charset($charset, $this->connection);

        $this->errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);

        return true;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function useExistingConnection($connection)
    {
        $this->connection = $connection;
        $this->errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);
    }

    public function close()
    {
        if (!$this->connection) {
            return;
        }
        mysql_close($this->connection);
        $this->connection = null;

        $this->errorHandler->destroy();
        $this->errorHandler = null;
    }

    public function query($query, $args = null, $flags = 0)
    {

        if (!($flags & (Database::FILTER_ALL | Database::FILTER_COLUMN | Database::FILTER_FIELD | Database::FILTER_ROW))) {
            $flags = $flags | Database::FILTER_ALL;
        }

        if (!($flags & (Database::TYPE_ASSOC | Database::TYPE_NUM))) {
            $flags = $flags | Database::TYPE_ASSOC;
        }

        $query = $this->prepareQuery($query, $args, true);
        $this->errorHandler->register($query);
        $q = mysql_query($query, $this->connection);
        if ($q === false) {
            trigger_error(mysql_error($this->connection), E_USER_ERROR);
        }
        $this->errorHandler->unRegister();

        if (!$q || mysql_num_rows($q) == 0) {
            if (($flags & Database::FILTER_COLUMN) || ($flags & Database::FILTER_ARRAY) || ($flags & Database::FILTER_ROW)) {
                return array();
            }

            if ($flags & Database::FILTER_FIELD) {
                return null;
            }

            return new DatabaseMySqlReader();
        }

        if ($flags & Database::FILTER_FIELD) {
            $data = mysql_fetch_row($q);

            return $data[0];
        }

        if ($flags & Database::FILTER_COLUMN) {
            $result = array();
            while ($data = mysql_fetch_row($q)) {
                array_push($result, $data[0]);
            }

            return $result;
        }

        $reader = new DatabaseMySqlReader($q, $flags);

        if ($flags & Database::FILTER_ROW) {
            return $reader->read();
        }

        if ($flags & Database::FILTER_ARRAY) {
            $res = array();
            while ($data = $reader->read()) {
                $res[] = $data;
            }

            return $res;
        }

        return $reader;
    }

    public function exec($query, $args = null, $flags = 0)
    {
        $query = $this->prepareQuery($query, $args, true);

        $this->errorHandler->register($query);
        $res = mysql_query($query, $this->connection);
        if ($res === false) {
            trigger_error(mysql_error($this->connection), E_USER_ERROR);
        }
        $this->errorHandler->unRegister();

        if ($res === false) {
            return false;
        }
        if (mysql_insert_id($this->connection) == 0) {
            return mysql_affected_rows($this->connection);
        }

        return mysql_insert_id($this->connection);
    }

    private static $filterArraySearch;
    private static $filterArrayReplace = array('\\\\', '\\"', '\\0', '\\n', '\\r', '\\Z', '\\\'');

    public function prepareQueryFilterCallback($replaces)
    {
        foreach ($replaces as $key => $replace) {
            $type = $replace[0];
            $val = $replace[1];

            if ($type == 's') {
                $replaces[$key] = str_replace(self::$filterArraySearch, self::$filterArrayReplace, $val);
            } else {
                if ($type == 't') {
                    $replaces[$key] = '"' . str_replace(
                        self::$filterArraySearch,
                        self::$filterArrayReplace,
                        $val
                    ) . '"';
                } else {
                    if ($type == 'd') {
                        $replaces[$key] = '"' . date('Y-m-d H:i:s', $val) . '"';
                    } else {
                        if ($type == 'b') {
                            $replaces[$key] = intval($val);
                        } else {
                            if ($type == 'S' || $type == 'T') {
                                $r = array();
                                foreach ($val as $v) {
                                    $r[] = '"' . str_replace(
                                        self::$filterArraySearch,
                                        self::$filterArrayReplace,
                                        $v
                                    ) . '"';
                                }
                                $replaces[$key] = implode(',', $r);
                            }
                        }
                    }
                }
            }
        }

        return $replaces;
    }

    public function insert($table, $fields, $values, $nestedRows = null, $rowsPerInsert = 100, $returnQueries = false)
    {
        $queries = array();

        $fieldSnippet = ArrayTools::implode(array_keys($fields), ',', '`', '`');
        $typeSnippet = '(' . implode(',', array_values($fields)) . ')';

        $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
        $args = array();

        $rowCount = 0;

        if ($nestedRows) {
            $firstRow = true;
            foreach ($nestedRows as $row) {
                $rowCount++;

                if ($firstRow) {
                    $firstRow = false;
                    $q .= $typeSnippet;
                } else {
                    $q .= ',' . $typeSnippet;
                }
                $args = array_combine($args, $row);

                if ($rowCount > $rowsPerInsert) {
                    if ($returnQueries) {
                        $queries[] = $this->prepareQuery($q, $args);
                    } else {
                        $this->exec($q, $args);
                    }

                    $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
                    $args = array();
                    $rowCount = 0;
                }
            }

            if (count($args)) {
                if ($returnQueries) {
                    $queries[] = $this->prepareQuery($q, $args);
                } else {
                    $this->exec($q, $args);
                }
            }
        } else {
            $fieldCount = count($fields);
            $rows = count($values) / $fieldCount;
            $rowCount = 0;
            $i = 0;
            $isFirstRow = true;
            while ($rows) {
                $rows--;
                $rowCount++;

                $field = $fieldCount;
                while ($field--) {
                    $args[] = $values[$i];
                    $i++;
                }

                if ($isFirstRow) {
                    $isFirstRow = false;
                    $q .= $typeSnippet;
                } else {
                    $q .= ',' . $typeSnippet;
                }

                if ($rowCount > $rowsPerInsert) {
                    if ($returnQueries) {
                        $queries[] = $this->prepareQuery($q, $args);
                    } else {
                        $this->exec($q, $args);
                    }

                    $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
                    $args = array();
                    $rowCount = 0;
                }
            }

            if (count($args)) {
                if ($returnQueries) {
                    $queries[] = $this->prepareQuery($q, $args);
                } else {
                    $this->exec($q, $args);
                }
            }
        }

        if ($returnQueries) {
            return $queries;
        }

        return null;
    }
}
