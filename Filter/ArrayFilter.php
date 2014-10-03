<?php
namespace Cyantree\Grout\Filter;

class ArrayFilter
{
    public $data;

    public $storeAsObject = false;

    public static function filter($value, $storeAsObject = false){
        return new ArrayFilter($value, $storeAsObject);
    }

    public function __construct($data = null, $storeAsObject = false)
    {
        $this->storeAsObject = $storeAsObject;
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
        if ($this->has($key)) {
            $data = $this->get($key);

        } else {
            $data = $this->storeAsObject ? new \stdClass() : array();
//            $this->set($key, $data);
        }

        return new ArrayFilter($data, $this->storeAsObject);
    }

    public function has($key)
    {
        return $this->data !== null && (
            ($this->storeAsObject && property_exists($this->data, $key)) ||
            (!$this->storeAsObject && array_key_exists($key, $this->data))
        );
    }

    public function getData()
    {
        if($this->data === null){
            if ($this->storeAsObject) {
                return new \stdClass();

            } else {
                return array();
            }
        }

        return $this->data;
    }

    public function setData($data)
    {
        if ($this->storeAsObject) {
            if ($data === null) {
                $data = new \stdClass();

            } elseif (is_array($data)) {
                $data = json_decode(json_encode($data)); // TODO: Doesn't work if data is non associative array [1, 2, 3]
            }

        } else {
            if ($data === null) {
                $data = array();

            } elseif (is_object($data)) {
                $data = json_decode(json_encode($data), true);
            }
        }

        $this->data = $data;
    }

    public function get($key, $defaultValue = null)
    {
        if ($this->data === null) {
            return $defaultValue;
        }

        if ($this->storeAsObject && property_exists($this->data, $key)) {
            return $this->data->{$key};

        } elseif (!$this->storeAsObject && array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $defaultValue;
    }

    public function needs($key){
        if($this->data === null){
            trigger_error('Key '.$key.' not found in data');
            return null;
        }

        if ($this->storeAsObject && property_exists($this->data, $key)) {
            return $this->data->{$key};

        } elseif (!$this->storeAsObject && array_key_exists($key, $this->data)) {
            return $this->data[$key];

        } else {
            trigger_error('Key '.$key.' not found in data');
            return null;
        }
    }

    public function set($key, $value)
    {
        if ($this->storeAsObject) {
            $this->data->{$key} = $value;

        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function setAsFilter($key, $value)
    {
        return $this->set($key, ArrayFilter::filter($value, $this->storeAsObject)->getData());
    }

    public function delete($key)
    {
        if($this->data === null){
            return;
        }

        if (!is_array($key)) {
            $key = array($key);
        }

        foreach($key as $k){
            if ($this->storeAsObject && property_exists($this->data, $k)) {
                unset($this->data->$k);

            } elseif (!$this->storeAsObject && array_key_exists($k, $this->data)) {
                unset($this->data[$k]);
            }
        }
    }
}