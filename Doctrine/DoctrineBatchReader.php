<?php
namespace Cyantree\Grout\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrineBatchReader
{
    public $clearEntitiesOnBatch = null;

    public $usePaginator = true;
    public $resultsPerBatch = 500;
    public $offset = 0; // TODO: Necessary? Will be retrieved from query
    public $limit = 0; // TODO: Necessary? Will be retrieved from query

    private $countTotal = 0;

    private $index = 0;
    private $count = 0;
    private $moreBatchesAvailable = true;

    public $results;

    public $hydrationMode = Query::HYDRATE_OBJECT;

    /** @var Query */
    private $query;

    /** @var Paginator */
    private $paginator;

    private function getNextBatch()
    {
        $this->clearEntitiesOnBatch();

        if (!$this->moreBatchesAvailable) {
            $this->index = 0;
            $this->count = 0;
            $this->results = array();
            return;
        }

        if (!$this->limit && !$this->resultsPerBatch && !$this->offset) {
            if (!$this->countTotal) {
                $this->query->setFirstResult(null);
                $this->query->setMaxResults(null);
                $this->results = $this->query->getResult($this->hydrationMode);

            } else {
                $this->results = array();
            }

            $this->moreBatchesAvailable = false;

        } else {
            if ($this->resultsPerBatch) {
                $maxResults = $this->limit ? min($this->resultsPerBatch, $this->limit - $this->countTotal) : $this->resultsPerBatch;

            } else {
                $maxResults = $this->limit ? $this->limit : null;
            }

            if ($maxResults > 0 || $maxResults === null) {
                $this->query->setFirstResult($this->offset ? $this->offset : null)
                        ->setMaxResults($maxResults);

                if ($this->usePaginator) {
                    $this->paginator = new Paginator($this->query);

                    $this->results = array();
                    foreach ($this->paginator as $result) {
                        $this->results[] = $result;
                    }

                } else {
                    $this->results = $this->query->getResult($this->hydrationMode);
                }

            } else {
                $this->results = array();
            }

            if (!$this->resultsPerBatch) {
                $this->moreBatchesAvailable = false;

            } else {
                $this->moreBatchesAvailable = count($this->results) == $this->resultsPerBatch;
            }
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
            if ($this->moreBatchesAvailable) {
                $this->getNextBatch();

            } else {
                return null;
            }
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
