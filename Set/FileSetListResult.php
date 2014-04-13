<?php
namespace Cyantree\Grout\Set;

class FileSetListResult extends SetListResult
{
    /** @var FileSet */
    private $_set;

    private $_ids;
    public $count;
    public $countAll;

    public function __construct($set, $ids)
    {
        $this->_set = $set;
        $this->_ids = $ids;
        $this->count = count($ids);
    }

    public function getNext()
    {
        $file = array_shift($this->_ids);

        if ($file === null) {
            return null;
        }
        $this->_set->loadById($file);

        return $this->_set;
    }
}