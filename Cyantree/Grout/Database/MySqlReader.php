<?php
namespace Cyantree\Grout\Database;

class DatabaseMySqlReader extends DatabaseReader
{
    private $_flags;
    private $_data;

    function __construct($data = null, $flags = null)
    {
        $this->_flags = $flags;
        $this->_data = $data;
    }

    public function hasResults()
    {
        return $this->_data != null;
    }

    public function read()
    {
        if ($this->_flags & Database::TYPE_ASSOC && $this->_flags & Database::TYPE_NUM) {
            return mysql_fetch_array($this->_data);
        }

        if ($this->_flags & Database::TYPE_NUM) {
            return mysql_fetch_row($this->_data);
        }

        if ($this->_flags & Database::TYPE_ASSOC) {
            return mysql_fetch_assoc($this->_data);
        }

        return null;
    }
}