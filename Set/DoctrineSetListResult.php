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

    public function __construct($set, Query $query)
    {
        $this->reader = new DoctrineBatchReader();
        $this->reader->setQuery($query);

        $this->set = $set;
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
}
