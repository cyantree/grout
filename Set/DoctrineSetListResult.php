<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Doctrine\DoctrineBatchReader;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrineSetListResult extends SetListResult
{
    /** @var DoctrineSet */
    public $set;

    /** @var DoctrineBatchReader */
    public $reader;

    /** @var Query */
    private $query;

    private $countAll;

    public function __construct(DoctrineSet $set, Query $query)
    {
        parent::__construct($set);

        $this->query = $query;
        $this->reader = new DoctrineBatchReader();
        $this->reader->setQuery($query);
        $this->reader->clearEntitiesOnBatch = true;
    }

    public function getNext()
    {
        $entity = $this->reader->getNext();

        if ($entity === null) {
            return null;
        }

        $this->set->setEntity($entity);

        return $this->set;
    }

    public function getNextEntity()
    {
        return $this->reader->getNext();
    }

    public function getAllEntites()
    {
        $e = array();

        while ($entity = $this->getNextEntity()) {
            $e[] = $entity;
        }

        return $e;
    }

    public function getCountAll()
    {
        if ($this->countAll === null) {
            $p = new Paginator($this->query, true);
            $this->countAll = count($p);
        }

        return $this->countAll;
    }
}
