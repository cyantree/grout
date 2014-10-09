<?php
namespace Cyantree\Grout\Filter;

class ListFilter extends ValueFilter
{
    public static function filter($value)
    {
        return new ListFilter($value);
    }

    public function match($acceptedValues, $defaultValue = null)
    {
        if (!in_array($this->value, $acceptedValues)) {
            $this->value = $defaultValue;
        }

        return $this;
    }
}
