<?php
namespace Cyantree\Grout\Database;

use SQLite3Result;

class SqliteReader extends DatabaseReader
{
    private $_flags;

    /** @var SQLite3Result */
    private $_data;
    private $_currentSet;

    function __construct($data = null, $flags = null)
    {
        $this->_flags = $flags;
        $this->_data = $data;

        $this->_readNext();
    }

    private function _readNext()
    {
        $this->_currentSet = null;

        if ($this->_flags & Database::TYPE_NUM) {
            $this->_currentSet = $this->_data->fetchArray(SQLITE3_NUM);
        } else {
            if ($this->_flags & Database::TYPE_ASSOC) {
                $this->_currentSet = $this->_data->fetchArray(SQLITE3_ASSOC);
            } else {
                if ($this->_flags & Database::TYPE_ASSOC && $this->_flags & Database::TYPE_NUM) {
                    $this->_currentSet = $this->_data->fetchArray(SQLITE3_BOTH);
                }
            }
        }

        if (!$this->_currentSet) {
            $this->_data->finalize();
            $this->_data = null;
        }
    }

    public function hasResults()
    {
        return $this->_data != null;
    }

    public function read()
    {
        $res = $this->_currentSet;

        if ($res) {
            $this->_readNext();
        }

        return $res;
    }
}
