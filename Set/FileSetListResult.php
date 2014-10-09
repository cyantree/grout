<?php
namespace Cyantree\Grout\Set;

class FileSetListResult extends SetListResult
{
    /** @var FileSet */
    private $set;

    private $ids;
    public $count;
    public $countAll;

    public function __construct($set, $ids)
    {
        $this->set = $set;
        $this->ids = $ids;
        $this->count = count($ids);
    }

    public function getNext()
    {
        $file = array_shift($this->ids);

        if ($file === null) {
            return null;
        }
        $this->set->loadById($file);

        return $this->set;
    }
}
