<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Database\Entity\Entity;
use Cyantree\Grout\Filter\ArrayFilter;
use Doctrine\ORM\EntityManager;

class DoctrineSet extends Set
{
    public $entity;

    /** @var SetTypeCapabilities */
    public static $capabilities;

    /** @return EntityManager */
    abstract protected function _getEntityManager();

    abstract protected function _getEntityClass();

    protected function _getIdField()
    {
        return 'id';
    }

    public function getId()
    {
        return $this->entity ? $this->entity->{$this->_getIdField()} : null;
    }

    public function setId($id)
    {
        $this->entity->{$this->_getIdField()} = $id;
    }

    public function createNew()
    {
        $c = $this->_getEntityClass();

        $this->setEntity(new $c());

        return true;
    }

    public function loadById($id)
    {
        $e = $this->_getEntityManager()->find($this->_getEntityClass(), $id);

        if($e){
            $this->setEntity($e);
        }

        return $e != null;
    }

    public function getData()
    {
        $a = array();

        foreach($this->contents as $name => $content){
            if($content->storeInSet){
                $a[$name] = $content->getData();
            }
        }

        return $a;
    }

    protected function _getListQueryData(ArrayFilter $options)
    {
        return array(
            'clauses' => array(
                'select' => '{e}',
                'from' => '{entity}',
                'where' => '{where}',
                'order' => '{order}'
            ),
            'select' => array(
                'query' => 'SELECT {select-clause} FROM {from-clause} WHERE {where-clause} ORDER BY {order-clause}',
                'parameters' => array(),
                'defaultOrder' => null,
            ),
            'count' => array(
                'query' => 'SELECT COUNT({e}) FROM {from-clause} WHERE {where-clause}',
                'parameters' => array(),
            ),
            'searchQueries' => array(),
            'parameters' => array()
        );
    }


    protected function _collectData()
    {
        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $this->entity->{$name} = $content->getData();
            }
        }
    }

    protected function _doDelete()
    {
        $this->_getEntityManager()->remove($this->entity);
        $this->_getEntityManager()->flush();
    }


    protected function _doSave()
    {
        $this->_getEntityManager()->persist($this->entity);
        $this->_getEntityManager()->flush();
    }

    public function setEntity($e)
    {
        $this->entity = $e;

        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $content->setData($e->{$name});
            }
        }

        $this->_onLoaded();
    }

    public function listSets($options)
    {
        $options = new ArrayFilter($options);

        $search = $options->get('search');
        $sorting = $options->asFilter('sort');
        $sortingField = $sorting->get('field');
        $sortingDirection = $sorting->get('direction');
        $offset = $options->get('offset', 0);
        $count = $options->get('count', 0);

        $parameters = array();

        // Create queries
        $data = $this->_getListQueryData($options);

        // Create search queries
        $searchQueries = &$data['searchQueries'];
        if($search != ''){
            foreach($this->contents as $content){
                if($content->searchable){
                    $parameters['search'] = '%' . $search . '%';
                    $searchQueries[] = 'e.' . $content->name . ' LIKE :search';
                }
            }
        }

        // Create query parts
        if($searchQueries){
            $filterClause = '(' . implode(' OR ', $searchQueries) . ')';
        }else{
            $filterClause = '1 = 1';
        }

        // Check for ordering
        $orderClause = '';
        if ($sortingField) {
            foreach($this->contents as $content){
                if($content->sortable && $content->name == $sortingField){
                    $orderClause = 'e.' . $content->name . ' ' . $sortingDirection;
                    break;
                }
            }
        }

        if($orderClause === '') {
            if ($data['select']['defaultOrder']) {
                $orderClause = $data['select']['defaultOrder'];
            } else {
                $orderField = $this->config->get('order');
                if ($orderField) {
                    $orderClause = 'e.'.$orderField;
                } else {
                    $identifiers = $this->_getEntityManager()->getClassMetadata($this->_getEntityClass())->getIdentifierFieldNames();
                    $orderClause = 'e.'.$identifiers[0].' DESC';
                }
            }
        }

        $queryLookUps = array(
            '{where}',
            '{order}',
            '{e}',
            '{entity}',
        );
        $queryReplaces = array(
            $filterClause,
            $orderClause,
            'e',
            $this->_getEntityClass() . ' e',
        );

        $queryClauseLookUps = array(
            '{select-clause}',
            '{where-clause}',
            '{order-clause}',
            '{from-clause}'
        );
        $queryClauseReplaces = array(
            $data['clauses']['select'],
            $data['clauses']['where'],
            $data['clauses']['order'],
            $data['clauses']['from']
        );

        // Get items
        $queryData = $data['select'];
        $query = str_replace($queryClauseLookUps, $queryClauseReplaces, $queryData['query']);
        $query = str_replace($queryLookUps, $queryReplaces, $query);
        $parameters = array_merge($parameters, $queryData['parameters'], $data['parameters']);

        $query = $this->_getEntityManager()->createQuery($query);

        if ($offset) {
            $query->setFirstResult($offset);
        }

        if ($count) {
            $query->setMaxResults($count);
        }

        if ($parameters) {
            $query->setParameters($parameters);
        }

        $result = new DoctrineSetListResult($this, $query->getResult());

        // Get count
        $queryData = $data['count'];
        $query = str_replace($queryClauseLookUps, $queryClauseReplaces, $queryData['query']);
        $query = str_replace($queryLookUps, $queryReplaces, $query);

        $query = $this->_getEntityManager()->createQuery($query);
        if ($parameters) {
            $query->setParameters($parameters);
        }

        $result->countAll = $query->getSingleScalarResult();

        return $result;
    }

    public function getCapabilities()
    {
        if (!DoctrineSet::$capabilities) {
            $c = DoctrineSet::$capabilities = new SetTypeCapabilities();
            $c->pagination = $c->sort = $c->search = true;
        }

        return self::$capabilities;
    }
}

class DoctrineSetListResult extends SetListResult
{
    /** @var DoctrineSet */
    private $_set;

    private $_entities;

    public function __construct($set, $entities)
    {
        $this->_set = $set;
        $this->_entities = $entities;
        $this->count = count($entities);
    }
    public function getNext()
    {
        $entity = array_shift($this->_entities);

        if ($entity === null) {
            return null;
        }

        $this->_set->setEntity($entity);

        return $this->_set;
    }
}