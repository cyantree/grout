<?php
namespace Cyantree\Grout\Filter;

class ArrayFilter
{
    /** @var array */
    public $data;

    public static function filter($value){
        return new ArrayFilter($value);
    }

    public function __construct($data = null)
    {
        $this->data = $data;
    }

    public function asString($key, $defaultValue = null)
    {
        return new StringFilter($this->get($key, $defaultValue));
    }

    public function asList($key, $defaultValue = null)
    {
        return new ListFilter($this->get($key, $defaultValue));
    }

    public function asFloat($key, $defaultValue = null)
    {
        return new NumberFilter($this->get($key, $defaultValue));
    }

    public function asInt($key, $defaultValue = null)
    {
        return new NumberFilter(intval($this->get($key, $defaultValue)));
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
        if($this->data === null){
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

    public function needs($key){
        if($this->data === null){
            trigger_error('Key '.$key.' not found in array');
            return null;
        }

        if(!array_key_exists($key, $this->data)){
            trigger_error('Key '.$key.' not found in array');
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

    public function delete($key)
    {
        if($this->data === null){
            return;
        }

        if(is_array($key)){
            foreach($key as $k){
                if(array_key_exists($k, $this->data)){
                    unset($this->data[$k]);
                }
            }

        }elseif(array_key_exists($key, $this->data)){
            unset($this->data[$key]);
        }
    }
}