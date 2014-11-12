<?php
namespace Cyantree\Grout\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    /** @var Query */
    private $query;

    /** @var Paginator */
    private $paginator;

    private function getNextBatch()
    {
        $this->clearEntitiesOnBatch();

        if (!$this->count || $this->count == $this->resultsPerBatch) {
            $maxResults = $this->limit ? min($this->resultsPerBatch, $this->limit - $this->countTotal) : $this->resultsPerBatch;

            if ($maxResults) {
                $this->query->setFirstResult($this->offset)
                        ->setMaxResults($maxResults);

                $this->paginator = new Paginator($this->query);

                $this->results = array();
                foreach ($this->paginator as $result) {
                    $this->results[] = $result;
                }

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
        $this->clearEntitiesOnBatch();

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

    private function clearEntitiesOnBatch()
    {
        if ($this->clearEntitiesOnBatch) {
            $em = $this->query->getEntityManager();

            if ($this->clearEntitiesOnBatch === true) {
                $em->clear();

            } else {
                foreach ($this->clearEntitiesOnBatch as $e) {
                    $em->clear($e);
                }
            }
        }
    }
}
