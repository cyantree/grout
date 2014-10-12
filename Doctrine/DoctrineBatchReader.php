<?php
namespace Cyantree\Grout\Doctrine;

use Doctrine\ORM\Query;

class DoctrineBatchReader
{
    public $clearEntitiesOnBatch = null;

    public $resultsPerBatch = 500;
    public $offset = 0;
    public $limit = 0;

    private $countTotal = 0;

    private $index = 0;
    private $count = 0;

    public $results;

    public $hydrationMode = Query::HYDRATE_OBJECT;

    /** @var Query */
    private $query;

    private function getNextBatch()
    {
        if ($this->clearEntitiesOnBatch) {
            $em = $this->query->getEntityManager();

            foreach ($this->clearEntitiesOnBatch as $e) {
                $em->clear($e);
            }
        }

        if (!$this->count || $this->count == $this->resultsPerBatch) {
            $maxResults = $this->limit ? min($this->resultsPerBatch, $this->limit - $this->countTotal) : $this->resultsPerBatch;

            if ($maxResults) {
                $this->results = $this->query->setFirstResult($this->offset)
                        ->setMaxResults($maxResults)
                        ->getResult($this->hydrationMode);

            } else {
                $this->results = array();
            }

        } else {
            $this->results = array();
        }

        $this->offset += $this->resultsPerBatch;
        $this->index = 0;
        $this->count = count($this->results);
        $this->countTotal += $this->count;
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

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
        $this->offset = $query->getFirstResult();
        $this->limit = $query->getMaxResults();
    }
}
