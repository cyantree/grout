<?php
namespace Cyantree\Grout\Set;

class SetMessage
{
    public $content;
    public $code;
    public $message;
    public $values;

    public function __toString()
    {
        return $this->values ? str_replace(array_keys($this->values), array_values($this->values), $this->message) : $this->message;
    }
}
