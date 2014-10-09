<?php
namespace Cyantree\Grout\Set;

class DoctrineSetListResult extends SetListResult
{
    /** @var DoctrineSet */
    private $set;

    private $entities;

    public function __construct($set, $entities)
    {
        $this->set = $set;
        $this->entities = $entities;
        $this->count = count($entities);
    }
    public function getNext()
    {
        $entity = array_shift($this->entities);

        if ($entity === null) {
            return null;
        }

        $this->set->setEntity($entity);

        return $this->set;
    }
}
