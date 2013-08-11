<?php
namespace Cyantree\Grout\Set;

class ContentMessage
{
    public $id;
    public $message;
    public $args;

    public function toString()
    {
        return str_replace(array_keys($this->args), array_values($this->args), $this->message);
    }
}