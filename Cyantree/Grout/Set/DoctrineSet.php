<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Database\Entity\Entity;
use Doctrine\ORM\EntityManager;

class DoctrineSet extends Set
{
    public $entity;
    public $entityClass;

    /** @var EntityManager */
    public $entityManager;

    public function getId()
    {
        return $this->entity ? $this->entity->id : null;
    }

    public function setId($id)
    {
        $this->entity->id = $id;
    }

    public function createNew()
    {
        $c = $this->entityClass;

        $this->setEntity(new $c());

        return true;
    }

    public function loadById($id)
    {
        $e = $this->entityManager->find($this->entityClass, $id);

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
                $a[$name] = $content->encode();
            }
        }

        return $a;
    }


    protected function _collectData()
    {
        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $this->entity->{$name} = $content->encode();
            }
        }
    }

    protected function _doDelete()
    {
        $this->entityManager->remove($this->entity);
        $this->entityManager->flush();
    }


    protected function _doSave()
    {
        $this->entityManager->persist($this->entity);
        $this->entityManager->flush();
    }

    public function setEntity($e)
    {
        $this->entity = $e;

        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $content->decode($e->{$name});
            }
        }

        $this->_onLoaded();
    }
}