<?php
namespace Cyantree\Grout\Database;

use Cyantree\Grout\ErrorHandler;
use Cyantree\Grout\Tools\ArrayTools;
use SQLite3;

class SqliteConnection extends DatabaseConnection
{
    public $id = 'SQLite';

    /** @var SQLite3 */
    private $connection;

    public $conversionTarget = 'sqlite';

    public function connect($file)
    {
        if ($this->connection) {
            return true;
        }
        $this->connection = new SQLite3($file);
        if (!$this->connection) {
            $this->connection = null;

            return false;
        }

        $this->errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);

        return true;
    }

    /** @return SQLite3 */
    public function getConnection()
    {
        return $this->connection;
    }

    /** @param $connection SQLite3 */
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

        $this->connection->close();
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
        $q = $this->connection->query($query);
        $this->errorHandler->unRegister();

        if (!$q) {
            if (($flags & Database::FILTER_COLUMN) || ($flags & Database::FILTER_ARRAY) || ($flags & Database::FILTER_ROW)) {
                return array();
            }

            if ($flags & Database::FILTER_FIELD) {
                return null;
            }

            return new SqliteReader();
        }

        if ($flags & Database::FILTER_FIELD) {
            $data = $q->fetchArray(SQLITE3_NUM);

            return $data[0];
        }

        if ($flags & Database::FILTER_COLUMN) {
            $result = array();
            while ($data = $q->fetchArray(SQLITE3_NUM)) {
                array_push($result, $data[0]);
            }

            return $result;
        }

        $reader = new SqliteReader($q, $flags);

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
        $result = $this->connection->exec($query);
        $this->errorHandler->unRegister();

        if (!$result) {
            return false;
        }

        if ($this->connection->lastInsertRowID()) {
            return $this->connection->lastInsertRowID();
        }

        return $this->connection->changes();
    }

    public function insert($table, $fields, $values, $nestedRows = false, $rowsPerInsert = 100, $returnQueries = false)
    {
        $queries = array();

        $fieldSnippet = ArrayTools::implode(array_keys($fields), ',', '`', '`');
        $typeSnippet = '(' . implode(',', array_values($fields)) . ')';

        if ($nestedRows) {
            foreach ($values as $row) {
                $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES' . $typeSnippet;

                if ($returnQueries) {
                    $queries[] = $this->prepareQuery($q, $row);

                } else {
                    $this->exec($q, $row);
                }
            }

        } else {
            $fieldCount = count($fields);
            $rows = count($values) / $fieldCount;
            $i = 0;
            $args = array();
            while ($rows) {
                $field = $fieldCount;
                while ($field--) {
                    $args[] = $values[$i];
                    $i++;
                }

                $q = 'INSERT INTO ' . $table . ' (' . $fieldSnippet . ')VALUES' . $typeSnippet;

                if ($returnQueries) {
                    $queries[] = $this->prepareQuery($q, $args);

                } else {
                    $this->exec($q, $args);
                }
                $args = array();
            }
        }

        if ($returnQueries) {
            return $queries;
        }

        return null;
    }

    private static $filterArraySearch = array('"', "\0");
    private static $filterArrayReplace = array('""', '');

    public function prepareQueryFilterCallback($replaces)
    {
        foreach ($replaces as $key => $replace) {
            $type = $replace[0];
            $val = $replace[1];

            if ($type == 's') {
                $replaces[$key] = str_replace(self::$filterArraySearch, self::$filterArrayReplace, $val);

            } elseif ($type == 't') {
                $replaces[$key] = '"' . str_replace(self::$filterArraySearch, self::$filterArrayReplace, $val) . '"';

            } elseif ($type == 'd') {
                $replaces[$key] = '"' . date('Y-m-d H:i:s', $val) . '"';

            } elseif ($type == 'b') {
                $replaces[$key] = intval($val);

            } elseif ($type == 'S' || $type == 'T') {
                $r = array();
                foreach ($val as $v) {
                    $r[] = '"' . str_replace(self::$filterArraySearch, self::$filterArrayReplace, $v) . '"';
                }
                $replaces[$key] = implode(',', $r);
            }
        }

        return $replaces;
    }
}
