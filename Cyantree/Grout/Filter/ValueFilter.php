<?php
namespace Cyantree\Grout\Filter;

class ValueFilter
{
    public $value;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public static function filter($value){
        return new ValueFilter($value);
    }
}