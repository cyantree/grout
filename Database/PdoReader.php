<?php
namespace Cyantree\Grout\Database;

use PDO;
use PDOStatement;

class PdoReader extends DatabaseReader
{
    private $flags;

    /** @var PDOStatement */
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
            return $this->data->fetch(PDO::FETCH_BOTH);
        }

        if ($this->flags & Database::TYPE_NUM) {
            return $this->data->fetch(PDO::FETCH_NUM);
        }

        if ($this->flags & Database::TYPE_ASSOC) {
            return $this->data->fetch(PDO::FETCH_ASSOC);
        }

        return null;
    }
}
