<?php
namespace Cyantree\Grout\Set;

abstract class SetListResult
{
    /** @var Set */
    public $set;

    function __construct(Set $set)
    {
        $this->set = $set;
    }

    abstract public function getNext();

    abstract public function getCountAll();
}
