<?php
namespace Cyantree\Grout\Database;

class DatabaseMySqlReader extends DatabaseReader
{
    private $flags;
    private $data;

    public function __construct($data = null, $flags = null)
    {
        $this->flags = $flags;
        $this->data = $data;
    }

    public function hasResults()
    {
        return $this->data != null;
    }

    public function read()
    {
        if ($this->flags & Database::TYPE_ASSOC && $this->flags & Database::TYPE_NUM) {
            return mysql_fetch_array($this->data);
        }

        if ($this->flags & Database::TYPE_NUM) {
            return mysql_fetch_row($this->data);
        }

        if ($this->flags & Database::TYPE_ASSOC) {
            return mysql_fetch_assoc($this->data);
        }

        return null;
    }
}
