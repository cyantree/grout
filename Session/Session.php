<?php
namespace Cyantree\Grout\Session;

abstract class Session
{
    public $id;
    public $data;

    abstract public function load($id = null, $checkSession = true);

    abstract public function save();

    abstract public function reset();

    abstract public function isValid();
}
