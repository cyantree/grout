<?php
namespace Cyantree\Grout\Filter;

class StringFilter extends ValueFilter
{
    public static function filter($value){
        return new StringFilter($value);
    }

    public function limit($length)
    {
        $this->value = mb_substr($this->value, 0, $length);

        return $this;
    }

    public function inArray($array, $defaultValue = null)
    {
        if (!in_array($this->value, $array)) {
            $this->value = $defaultValue;
        }

        return $this;
    }

    public function match($pattern, $defaultValue = null)
    {
        if (!preg_match($pattern, $this->value)) {
            $this->value = $defaultValue;
        }

        return $this;
    }

    public function trim()
    {
        $this->value = trim($this->value);

        return $this;
    }

    public function asInput($length = 0, $multiline = false, $trim = true)
    {
        if ($length) {
            $this->limit($length);
        }
        if (!$multiline) {
            $this->asLine();
        } else {
            $this->asText();
        }
        if ($trim) {
            $this->trim();
        }

        return $this;
    }

    public function asLine()
    {
        $this->value = preg_replace('/[\x00-\x08]/', '', $this->value);
        $this->value = str_replace(array("\r", "\n", "\t"), array('', '', ''), $this->value);

        return $this;
    }

    public function asText()
    {
        $this->value = preg_replace('/[\x00-\x08]/', '', $this->value);

        return $this;
    }

    public function __toString()
    {
        return strval($this->value);
    }
}