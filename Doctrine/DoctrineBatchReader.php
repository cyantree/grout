<?php
namespace Cyantree\Grout\Doctrine;

use Doctrine\ORM\Query;

class DoctrineBatchReader
{
    public $clearEntitiesOnBatch = null;

    public $resultsPerBatch = 500;
    public $offset = 0;
    public $countTotal = 0;

    private $_index = 0;
    private $_count = 0;

    public $results;

    public $hydrationMode = Query::HYDRATE_OBJECT;

    /** @var Query */
    public $query;

    private function _getNextBatch()
    {
        if ($this->clearEntitiesOnBatch) {
            $em = $this->query->getEntityManager();

            foreach ($this->clearEntitiesOnBatch as $e) {
                $em->clear($e);
            }
        }

        if (!$this->_count || $this->_count == $this->resultsPerBatch) {
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
        $this->_index = 0;
        $this->_count = count($this->results);
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
        if ($this->results === null || $this->_index == $this->_count) {
            $this->_getNextBatch();
        }

        if ($this->_count == 0) {
            return null;
        }

        $result = $this->results[$this->_index];
        $this->_index++;

        return $result;
    }
}