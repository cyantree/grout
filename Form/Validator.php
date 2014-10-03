<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Checks\Check;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class Validator
{
    private $_currentValue;
    private $_currentId;

    public $errors = array();
    public $success = true;

    public $stopOnError = true;

    public function validate($id, $value = null)
    {
        $this->_currentValue = $value;
        $this->_currentId = $id;

        return $this;
    }

    public function validateField(ArrayFilter $filter, $field, $defaultValue = null)
    {
        $this->validate($field, $filter->get($field, $defaultValue));

        return $this;
    }

    public function getValue()
    {
        return $this->_currentValue;
    }

    public function getId()
    {
        return $this->_currentId;
    }

    private function _addError($code, $message)
    {
        if(!isset($this->errors[$this->_currentId])){
            $this->errors[$this->_currentId] = array();
        }

        $this->success = false;
        $this->errors[$this->_currentId][$code] = $message;
    }

    public function check(Check $check, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if (!$check->isValid($this->_currentValue)) {
            $this->_addError($check->id, ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function length($minLength = null, $maxLength = null, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        $len = mb_strlen($this->_currentValue);
        if ($minLength !== null && $len < $minLength) {
            $this->_addError('minLength', ArrayTools::get($options, 'message'));
        }

        if ($maxLength !== null && $len > $maxLength) {
            $this->_addError('maxLength', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function email($options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if (!StringTools::isEmailAddress($this->_currentValue)) {
            $this->_addError('email', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function integer($options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if (strval(intval($this->_currentValue)) !== strval($this->_currentValue)) {
            $this->_addError('integer', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function limit($min = null, $max = null, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if ($min !== null && $this->_currentValue < $min) {
            $this->_addError('limit', ArrayTools::get($options, 'message'));

        } elseif ($max !== null && $this->_currentValue > $max) {
            $this->_addError('limit', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function manual($isCorrect, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if(!$isCorrect){
            $this->_addError('manual', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function regEx($regEx, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if(!preg_match($regEx, $this->_currentValue)){
            $this->_addError('regEx', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function inArray($array, $options = null)
    {
        if($this->stopOnError && !$this->hasValidated($this->_currentId)){
            return $this;
        }

        if(!in_array($this->_currentValue, $array)){
            $this->_addError('inArray', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function hasValidated($id = null)
    {
        if ($id === null) {
            $id = $this->_currentId;
        }
        return !isset($this->errors[$id]);
    }
}