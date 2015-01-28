<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Doctrine\DoctrineBatchReader;
use Doctrine\ORM\Query;

class DoctrineSetListResult extends SetListResult
{
    /** @var DoctrineSet */
    private $set;

    /** @var DoctrineBatchReader */
    private $reader;

    public function __construct(DoctrineSet $set, Query $query)
    {
        parent::__construct($set);

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
}
