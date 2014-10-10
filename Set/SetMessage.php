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
        if ($this->values) {
            $message = str_replace(array_keys($this->values), array_values($this->values), $this->message);

        } else {
            $message = $this->message;
        }

        return $message;
    }
}
