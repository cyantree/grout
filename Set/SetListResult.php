<?php
namespace Cyantree\Grout\Set;

abstract class SetListResult
{
    public $countAll;

    abstract public function getNext();
}
