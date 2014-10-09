<?php
namespace Cyantree\Grout\Database;

use SQLite3Result;

class SqliteReader extends DatabaseReader
{
    private $flags;

    /** @var SQLite3Result */
    private $data;
    private $currentSet;

    public function __construct($data = null, $flags = null)
    {
        $this->flags = $flags;
        $this->data = $data;

        $this->readNext();
    }

    private function readNext()
    {
        $this->currentSet = null;

        if ($this->flags & Database::TYPE_NUM) {
            $this->currentSet = $this->data->fetchArray(SQLITE3_NUM);
        } else {
            if ($this->flags & Database::TYPE_ASSOC) {
                $this->currentSet = $this->data->fetchArray(SQLITE3_ASSOC);
            } else {
                if ($this->flags & Database::TYPE_ASSOC && $this->flags & Database::TYPE_NUM) {
                    $this->currentSet = $this->data->fetchArray(SQLITE3_BOTH);
                }
            }
        }

        if (!$this->currentSet) {
            $this->data->finalize();
            $this->data = null;
        }
    }

    public function hasResults()
    {
        return $this->data != null;
    }

    public function read()
    {
        $res = $this->currentSet;

        if ($res) {
            $this->readNext();
        }

        return $res;
    }
}
