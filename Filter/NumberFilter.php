<?php
namespace Cyantree\Grout\Filter;

class NumberFilter extends ValueFilter
{
    public static function filter($value)
    {
        return new NumberFilter($value);
    }

    public function __construct($value = null)
    {
        $this->value = floatval($value);
    }

    public function limit($min = null, $max = null)
    {
        if ($min !== null && $this->value < $min) {
            $this->value = $min;
        } else {
            if ($max !== null && $this->value > $max) {
                $this->value = $max;
            }
        }

        return $this;
    }

    public function asInt()
    {
        $this->value = intval($this->value);

        return $this;
    }
}
