<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Checks\Check;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class Validator
{
    private $currentValue;
    private $currentId;

    public $errors = array();
    public $success = true;

    public $stopOnError = true;

    public function validate($id, $value = null)
    {
        $this->currentValue = $value;
        $this->currentId = $id;

        return $this;
    }

    public function validateField(ArrayFilter $filter, $field, $defaultValue = null)
    {
        $this->validate($field, $filter->get($field, $defaultValue));

        return $this;
    }

    public function getValue()
    {
        return $this->currentValue;
    }

    public function getId()
    {
        return $this->currentId;
    }

    private function addError($code, $message)
    {
        if (!isset($this->errors[$this->currentId])) {
            $this->errors[$this->currentId] = array();
        }

        $this->success = false;
        $this->errors[$this->currentId][$code] = $message;
    }

    public function check(Check $check, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (!$check->isValid($this->currentValue)) {
            $this->addError($check->id, ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function length($minLength = null, $maxLength = null, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        $len = mb_strlen($this->currentValue);
        if ($minLength !== null && $len < $minLength) {
            $this->addError('minLength', ArrayTools::get($options, 'message'));
        }

        if ($maxLength !== null && $len > $maxLength) {
            $this->addError('maxLength', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function email($options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (!StringTools::isEmailAddress($this->currentValue)) {
            $this->addError('email', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function integer($options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (strval(intval($this->currentValue)) !== strval($this->currentValue)) {
            $this->addError('integer', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function limit($min = null, $max = null, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if ($min !== null && $this->currentValue < $min) {
            $this->addError('limit', ArrayTools::get($options, 'message'));

        } elseif ($max !== null && $this->currentValue > $max) {
            $this->addError('limit', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function manual($isCorrect, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (!$isCorrect) {
            $this->addError('manual', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function regEx($regEx, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (!preg_match($regEx, $this->currentValue)) {
            $this->addError('regEx', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function inArray($array, $options = null)
    {
        if ($this->stopOnError && !$this->hasValidated($this->currentId)) {
            return $this;
        }

        if (!in_array($this->currentValue, $array)) {
            $this->addError('inArray', ArrayTools::get($options, 'message'));
        }

        return $this;
    }

    public function hasValidated($id = null)
    {
        if ($id === null) {
            $id = $this->currentId;
        }
        return !isset($this->errors[$id]);
    }
}
