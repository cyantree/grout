<?php
namespace Cyantree\Grout\Doctrine;

use Doctrine\ORM\Query;

class DoctrineBatchReader
{
    public $clearEntitiesOnBatch = null;

    public $resultsPerBatch = 500;
    public $offset = 0;
    public $countTotal = 0;

    private $index = 0;
    private $count = 0;

    public $results;

    public $hydrationMode = Query::HYDRATE_OBJECT;

    /** @var Query */
    public $query;

    private function getNextBatch()
    {
        if ($this->clearEntitiesOnBatch) {
            $em = $this->query->getEntityManager();

            foreach ($this->clearEntitiesOnBatch as $e) {
                $em->clear($e);
            }
        }

        if (!$this->count || $this->count == $this->resultsPerBatch) {
            if ($this->countTotal && $this->offset + $this->resultsPerBatch > $this->countTotal) {
                $maxResults = $this->countTotal - $this->offset;

            } else {
                $maxResults = $this->resultsPerBatch;
            }

            $this->results = $this->query->setFirstResult($this->offset)->setMaxResults($maxResults)->getResult($this->hydrationMode);

        } else {
            $this->results = array();
        }

        $this->offset += $this->resultsPerBatch;
        $this->index = 0;
        $this->count = count($this->results);
    }

    public function close()
    {
        if ($this->clearEntitiesOnBatch) {
            $em = $this->query->getEntityManager();

            foreach ($this->clearEntitiesOnBatch as $e) {
                $em->clear($e);
            }
        }

        $this->query = null;
        $this->results = null;
    }

    public function getNext()
    {
        if ($this->results === null || $this->index == $this->count) {
            $this->getNextBatch();
        }

        if ($this->count == 0) {
            return null;
        }

        $result = $this->results[$this->index];
        $this->index++;

        return $result;
    }
}
