<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Database\Entity\Entity;

abstract class EntitySet extends Set
{
    /** @var Entity */
    public $entity;

    public $entityClass;

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

        $this->setData(new $c());

        return true;
    }

    public function loadById($id)
    {
        $e = Entity::loadById($this->entityClass, $id);

        if($e){
            $this->setData($e);
        }

        return $e != null;
    }

    public function setData($data)
    {
        $this->entity = $data;

        foreach($this->contents as $name => $content)
        {
            if($content->storeInSet){
                $content->setData($data->{$name});
            }
        }
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
        $this->entity->delete();
    }


    protected function _doSave()
    {
        $this->entity->save();
    }
}