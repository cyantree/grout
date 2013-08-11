<?php
namespace Cyantree\Grout\Database;

use PDO;
use PDOStatement;

class PdoReader extends DatabaseReader
{
    private $_flags;

    /** @var PDOStatement */
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
            return $this->_data->fetch(PDO::FETCH_BOTH);
        }

        if ($this->_flags & Database::TYPE_NUM) {
            return $this->_data->fetch(PDO::FETCH_NUM);
        }

        if ($this->_flags & Database::TYPE_ASSOC) {
            return $this->_data->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }
}