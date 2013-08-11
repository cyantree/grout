<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;
use Doctrine\DBAL\Types\ArrayType;

class Validator
{
    private $_currentValue;
    private $_currentId;

    public $errors = array();
    public $success = true;

    public function validate($value, $id)
    {
        $this->_currentValue = $value;
        $this->_currentId = $id;

        return $this;
    }

    private function _addError($code, $message)
    {
        if(!isset($this->errors[$this->_currentId])){
            $this->errors[$this->_currentId] = array();
        }

        $this->success = false;
        $this->errors[$this->_currentId][$code] = $message;
    }

    public function length($minLength = null, $maxLength = null, $options = null)
    {
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
        if (!StringTools::isMailAddress($this->_currentValue)) {
            $this->_addError('email', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function manual($isCorrect, $options = null)
    {
        if(!$isCorrect){
            $this->_addError('incorrect', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function hasValidated($name)
    {
        return !isset($this->errors[$name]);
    }
}