<?php
namespace Cyantree\Grout\Database;

use Cyantree\Grout\ErrorHandler;
use Cyantree\Grout\Tools\ArrayTools;
use SQLite3;

class SqliteConnection extends DatabaseConnection
{
    public $id = 'SQLite';

    /** @var SQLite3 */
    private $_connection;

    public $conversionTarget = 'sqlite';

    public function connect($file)
    {
        if ($this->_connection) {
            return true;
        }
        $this->_connection = new SQLite3($file);
        if (!$this->_connection) {
            $this->_connection = null;

            return false;
        }

        $this->_errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);

        return true;
    }

    /** @return SQLite3 */
    public function getConnection()
    {
        return $this->_connection;
    }

    /** @param $connection SQLite3 */
    public function useExistingConnection($connection)
    {
        $this->_connection = $connection;

        $this->_errorHandler = ErrorHandler::getHandler(array($this, 'onError'), null, false);
    }

    public function close()
    {
        if (!$this->_connection) {
            return;
        }

        $this->_connection->close();
        $this->_connection = null;

        $this->_errorHandler->destroy();
        $this->_errorHandler = null;
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
        $q = $this->_connection->query($query);
        $this->_errorHandler->unRegister();

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

        $this->_errorHandler->register($query);
        $result = $this->_connection->exec($query);
        $this->_errorHandler->unRegister();

        if (!$result) {
            return false;
        }

        if ($this->_connection->lastInsertRowID()) {
            return $this->_connection->lastInsertRowID();
        }

        return $this->_connection->changes();
    }

    public function insert($table, $fields, $values, $nestedRows = false, $rowsPerInsert = 100, $returnQueries = false)
    {
        if ($returnQueries) {
            $queries = array();
        }

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

    private static $_filterArraySearch = array('"', "\0");
    private static $_filterArrayReplace = array('""', '');

    public function prepareQueryFilterCallback($replaces)
    {
        foreach ($replaces as $key => $replace) {
            $type = $replace[0];
            $val = $replace[1];

            if ($type == 's') {
                $replaces[$key] = str_replace(self::$_filterArraySearch, self::$_filterArrayReplace, $val);
            } else {
                if ($type == 't') {
                    $replaces[$key] = '"' . str_replace(self::$_filterArraySearch, self::$_filterArrayReplace, $val) . '"';
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
                                    $r[] = '"' . str_replace(self::$_filterArraySearch, self::$_filterArrayReplace, $v) . '"';
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
}
