<?php
namespace Cyantree\Grout\Filter;

class ListFilter extends ValueFilter
{
    public static function filter($value)
    {
        return new ListFilter($value);
    }

    public function match($acceptedValues, $defaultValue = null, $lookUpKeys = false)
    {
        if (($lookUpKeys && !array_key_exists($this->value, $acceptedValues))
            || (!$lookUpKeys && !in_array($this->value, $acceptedValues)
        )) {
            $this->value = $defaultValue;
        }

        return $this;
    }
}
