<?php
namespace Cyantree\Grout\Set;

class FileSetListResult extends SetListResult
{
    /** @var FileSet */
    public $set;

    private $ids;

    private $countAll;

    public function __construct($set, $ids)
    {
        $this->set = $set;
        $this->ids = $ids;
        $this->countAll = count($this->ids);
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

    public function getCountAll()
    {
        return $this->countAll;
    }
}
