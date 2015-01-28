<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

abstract class DoctrineSet extends Set
{
    public $entity;

    /** @var SetTypeCapabilities */
    public static $capabilities;

    /** @return EntityManager */
    abstract protected function getEntityManager();

    abstract protected function getEntityClass();

    protected function getIdField()
    {
        return 'id';
    }

    public function getId()
    {
        return $this->entity ? $this->entity->{$this->getIdField()} : null;
    }

    public function setId($id)
    {
        $this->entity->{$this->getIdField()} = $id;
    }

    public function createNew()
    {
        $c = $this->getEntityClass();

        $this->setEntity(new $c());

        return true;
    }

    public function loadById($id)
    {
        $e = $this->getEntityManager()->find($this->getEntityClass(), $id);

        if ($e) {
            $this->setEntity($e);
        }

        return $e != null;
    }

    /** @return DoctrineSetQueryData */
    protected function getListQueryData(ArrayFilter $options)
    {
        return new DoctrineSetQueryData();
    }


    protected function collectData()
    {
        foreach ($this->contents as $name => $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->storeInSet) {
                $this->entity->{$name} = $content->getValue();
            }
        }
    }

    protected function doDelete()
    {
        $this->getEntityManager()->remove($this->entity);
        $this->getEntityManager()->flush();
    }


    protected function doSave()
    {
        $this->getEntityManager()->persist($this->entity);
        $this->getEntityManager()->flush();
    }

    public function setEntity($e)
    {
        $this->entity = $e;

        foreach ($this->contents as $name => $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->storeInSet) {
                $content->setValue($e->{$name});
            }
        }

        $this->onLoaded();
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
        $queryData = $this->getListQueryData($options);

        // Create search queries
        if ($search != '') {
            foreach ($this->contents as $content) {
                if (!$content->enabled) {
                    continue;
                }

                if ($content->searchable) {
                    $parameters['search'] = '%' . $search . '%';
                    $queryData->searchClauses[] = 'e.' . $content->name . ' LIKE :search';
                }
            }
        }

        // Create query parts
        if ($queryData->searchClauses) {
            // TODO: Surround every clause with (...)
            $filterClause = '(' . implode(' OR ', $queryData->searchClauses) . ')';

        } else {
            $filterClause = '1 = 1';
        }

        // Check for ordering
        $orderClause = '';
        if ($sortingField) {
            foreach ($this->contents as $content) {
                if (!$content->enabled) {
                    continue;
                }

                if ($content->sortable && $content->name == $sortingField) {
                    $orderClause = 'e.' . $content->name . ' ' . $sortingDirection;
                    break;
                }
            }
        }

        if ($orderClause === '') {
            if ($queryData->defaultOrder) {
                $orderClause = $queryData->defaultOrder;

            } else {
                $orderField = $this->config->get('order');
                if ($orderField) {
                    $orderClause = 'e.' . $orderField;

                } else {
                    $identifiers = $this->getEntityManager()
                        ->getMetadataFactory()->getMetadataFor($this->getEntityClass())
                        ->getIdentifierFieldNames();
                    $orderClause = 'e.' . $identifiers[0] . ' DESC';
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
            $this->getEntityClass() . ' e',
        );

        $queryClauseLookUps = array(
            '{selectClause}',
            '{whereClause}',
            '{orderClause}',
            '{fromClause}'
        );
        $queryClauseReplaces = array(
            $queryData->selectClause,
            $queryData->whereClause,
            $queryData->orderClause,
            $queryData->fromClause
        );

        // Get items
        $query = str_replace($queryClauseLookUps, $queryClauseReplaces, $queryData->selectQuery);
        $query = str_replace($queryLookUps, $queryReplaces, $query);
        $parameters = array_merge($parameters, $queryData->parameters);

        $query = $this->getEntityManager()->createQuery($query);

        if ($offset) {
            $query->setFirstResult($offset);
        }

        if ($count) {
            $query->setMaxResults($count);
        }

        if ($parameters) {
            $query->setParameters($parameters);
        }

        $result = new DoctrineSetListResult($this, $query);

        if ($options->get('getCount', true)) {
            $p = new Paginator($query, true);
            $result->countAll = count($p);
        }

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
