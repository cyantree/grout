<?php
namespace Cyantree\Grout\Filter;

class ArrayFilter
{
    /** @var array */
    public $data;

    public static function filter($value)
    {
        return new ArrayFilter($value);
    }

    public function __construct($data = null)
    {
        $this->data = $data;
        $this->setData($data);
    }

    public function asString($key, $defaultValue = null)
    {
        $value = $this->get($key, $defaultValue);
        if (is_array($value) || is_object($value)) {
            $value = $defaultValue;

        } else {
            $value = strval($value);
        }
        return new StringFilter($value);
    }

    public function asList($key, $defaultValue = null)
    {
        return new ListFilter($this->get($key, $defaultValue));
    }

    public function asFloat($key, $defaultValue = null)
    {
        $value = $this->get($key, $defaultValue);
        if (is_array($value) || is_object($value)) {
            $value = $defaultValue;

        } else {
            $value = floatval($value);
        }
        return new NumberFilter($value);
    }

    public function asInt($key, $defaultValue = null)
    {
        $value = $this->get($key, $defaultValue);
        if (is_array($value) || is_object($value)) {
            $value = $defaultValue;

        } else {
            $value = intval($value);
        }
        return new NumberFilter($value);
    }

    public function asFilter($key)
    {
        $data = $this->get($key);
        if (!is_array($data)) {
            $data = null;
        }

        return new ArrayFilter($data);
    }

    public function has($key)
    {
        return $this->data !== null && array_key_exists($key, $this->data);
    }

    public function getData()
    {
        if ($this->data === null) {
            return array();
        }

        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function get($key, $defaultValue = null)
    {
        if ($this->data === null) {
            return $defaultValue;
        }

        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $defaultValue;
    }

    public function needs($key)
    {
        if ($this->data === null) {
            trigger_error('Key ' . $key . ' not found in data');
            return null;
        }

        if (!array_key_exists($key, $this->data)) {
            trigger_error('Key ' . $key . ' not found in data');
            return null;
        }

        return $this->data[$key];
    }

    public function set($key, $value)
    {
        if ($this->data === null) {
            $this->data = array();
        }

        $this->data[$key] = $value;

        return $this;
    }

    public function setAsFilter($key, $value)
    {
        return $this->set($key, ArrayFilter::filter($value)->getData());
    }

    public function delete($key)
    {
        if ($this->data === null) {
            return;
        }

        if (is_array($key)) {
            foreach ($key as $k) {
                if (array_key_exists($k, $this->data)) {
                    unset($this->data[$k]);
                }
            }

        } else {
            if (array_key_exists($key, $this->data)) {
                unset($this->data[$key]);
            }
        }
    }

    public function getMultiple($array, $associative = true)
    {
        $result = array();

        foreach ($array as $name => $default) {
            if (!is_string($name)) {
                $name = $default;
                $default = null;
            }

            $value = $this->get($name, $default);

            if ($associative) {
                $result[$name] = $value;

            } else {
                $result[] = $value;
            }
        }

        return $result;
    }
}
