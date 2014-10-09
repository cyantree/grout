<?php
namespace Cyantree\Grout\Database;

use Cyantree\Grout\ErrorHandler;
use Cyantree\Grout\Tools\ArrayTools;
use PDO;

class PdoConnection extends DatabaseConnection
{
    public $id = 'PDO';

    /** @var PDO */
    private $connection;

    public $conversionTarget = 'sql';

    public function __construct()
    {
        parent::__construct();

        if (self::$filterArraySearch === null) {
            self::$filterArraySearch = array('\\', '"', "\0", "\n", "\r", chr(26), "'");
        }
    }

    public function connectMySql($host, $user, $pass, $database, $charset = 'utf8', $port = 3306)
    {
        if ($this->connection) {
            return true;
        }

        $this->connection = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $database, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset));

        $this->_errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);

        return true;
    }

    /** @return PDO */
    public function getConnection()
    {
        return $this->connection;
    }

    /** @param $connection PDO */
    public function useExistingConnection($connection)
    {
        $this->connection = $connection;

        $this->_errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);
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

        $this->_errorHandler->register($query);
        $q = $this->connection->query($query);
        if ($q === false) {
            $e = $this->connection->errorInfo();
            trigger_error($e[2], E_USER_ERROR);
        }
        $this->_errorHandler->unRegister();

        if (!$q || $q->rowCount() == 0) {
            if (($flags & Database::FILTER_COLUMN) || ($flags & Database::FILTER_ARRAY) || ($flags & Database::FILTER_ROW)) {
                return array();
            }

            if ($flags & Database::FILTER_FIELD) {
                return null;
            }

            return new PdoReader();
        }

        if ($flags & Database::FILTER_FIELD) {
            $data = $q->fetch(PDO::FETCH_NUM);
            return $data[0];
        }

        if ($flags & Database::FILTER_COLUMN) {
            $result = array();
            while ($data = $q->fetchColumn(0)) {
                array_push($result, $data);
            }
            return $result;
        }

        $reader = new PdoReader($q, $flags);

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

        $this->_errorHandler->register($query);
        $affectedRows = $this->connection->exec($query);
        if ($affectedRows === false) {
            $e = $this->connection->errorInfo();
            trigger_error($e[2], E_USER_ERROR);
        }
        $this->_errorHandler->unRegister();

        if ($affectedRows === false) {
            return false;
        }

        $insertID = $this->connection->lastInsertId();
        if (!$insertID) {
            return $affectedRows;
        }
        return $insertID;
    }

    public function insert($table, $fields, $values, $nestedRows = false, $rowsPerInsert = 100, $returnQueries = false)
    {
        $queries = array();

        $fieldSnippet = ArrayTools::implode(array_keys($fields), ',', '`', '`');
        $typeSnippet = '(' . implode(',', array_values($fields)) . ')';

        $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
        $args = array();

        $rowCount = 0;

        if ($nestedRows) {
            $isFirstRow = true;
            foreach ($values as $row) {
                $rowCount++;

                if ($isFirstRow) {
                    $isFirstRow = false;
                    $q .= $typeSnippet;

                } else {
                    $q .= ',' . $typeSnippet;
                }
                $args = array_merge($args, $row);

                if ($rowCount >= $rowsPerInsert) {
                    if ($returnQueries) {
                        $queries[] = $this->prepareQuery($q, $args);

                    } else {
                        $this->exec($q, $args);
                    }

                    $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
                    $args = array();
                    $rowCount = 0;
                    $isFirstRow = true;
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

                if ($rowCount >= $rowsPerInsert) {
                    if ($returnQueries) {
                        $queries[] = $this->prepareQuery($q, $args);

                    } else {
                        $this->exec($q, $args);
                    }

                    $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES';
                    $args = array();
                    $rowCount = 0;
                    $isFirstRow = true;
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

    private static $filterArraySearch;
    private static $filterArrayReplace = array('\\\\', '\\"', '\\0', '\\n', '\\r', '\\Z', '\\\'');

    public function prepareQueryFilterCallback($replaces)
    {
        foreach ($replaces as $key => $replace) {
            $type = $replace[0];
            $val = $replace[1];

            if ($type == 's') {
                $replaces[$key] = str_replace(self::$filterArraySearch, self::$filterArrayReplace, $val);
            } elseif ($type == 't') {
                $replaces[$key] = '"' . str_replace(
                    self::$filterArraySearch,
                    self::$filterArrayReplace,
                    $val
                ) . '"';

            } elseif ($type == 'd') {
                $replaces[$key] = '"' . date('Y-m-d H:i:s', $val) . '"';

            } elseif ($type == 'b') {
                $replaces[$key] = intval($val);

            } elseif ($type == 'S' || $type == 'T') {
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

        return $replaces;
    }
}
