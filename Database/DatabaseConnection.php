<?php
namespace Cyantree\Grout\Database;

use Cyantree\Grout\ErrorHandler;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Tools\StringTools;
use Exception;

class DatabaseConnection
{
    public $id = 'DB';

    public $debug = false;

    public $globalQueryArgs = array();

    /** @var Events */
    public $events;

    public $countQueries = 0;

    public $conversionTarget;

    protected $_backupLastQuery = false;
    protected $_lastQuery;

    /** @var ErrorHandler */
    protected $_errorHandler;

    public function __construct()
    {
        if (!Database::$default) {
            Database::$default = $this;
        }
    }

    public function onError($error)
    {
        $query = $error->data;
        if (strlen($query) > 1000) {
            $error->data = substr($query, 0, 1000) . ' [...] [TRUNCATED]';
        }

        throw new Exception('SQL error: ' . $error->message . ' @ ' . $query);
    }

    /** @return string */
    public function prepareQuery($query, $args = null, $isInternalCall = false)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $query = StringTools::parse(
            $query,
            array_merge($this->globalQueryArgs, $args),
            array($this, 'prepareQueryFilterCallback')
        );

        if ($isInternalCall) {
            Database::$countQueries++;
            $this->countQueries++;

            if ($this->_backupLastQuery) {
                $this->_lastQuery = $query;
            }

            if ($this->debug && $this->events) {
                $this->events->trigger('log', '[' . $this->id . '] Query: ' . $query);
            }
        }

        return $query;
    }

    public function prepareQueryFilterCallback($replaces)
    {
        return $replaces;
    }

    /** @return int|void */
    public function exec($query, $args = null, $flags = 0)
    {
    }

    /** @return DatabaseReader|string|array|void */
    public function query($query, $args = null, $flags = 0)
    {
    }

    /** @return array|void */
    public function insert($table, $fields, $values, $nestedRows = false, $rowsPerInsert = 100, $returnQueries = false)
    {
    }

    public function backupLastQuery($flag)
    {
        $this->_backupLastQuery = $flag;
        if (!$flag) {
            $this->_lastQuery = '';
        }
    }

    protected function _destroy()
    {
        $this->_errorHandler->destroy();
        $this->_errorHandler = null;
    }


    /** @return string */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }
}
