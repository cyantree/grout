<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;

class Form
{
    public $data;

    /** @var ArrayFilter */
    public $dataIn;

    /** @var ArrayFilter */
    public $dataFiles;

    public $submitButton = array('next', 'submit');
    public $mode;
    public $isSubmit;

    /** @var FormStatus */
    public $status;

    public function execute()
    {
        $this->status = new FormStatus();

        // PreInit form
        $this->_preInit();

        if ($this->status->error) {
            $this->_deInit();
            return;
        }

        // New form, create data object
        $this->data = $this->_createDataObject();

        $this->isSubmit = $this->_isSubmit();

        // Init form
        $this->_init();

        // Form hasn't been submitted yet or an error has occurred, so end processing here
        if (!$this->isSubmit || $this->status->error) {
            $this->_deInit();
            return;
        }

        $this->_getData();

        if ($this->status->error) {
            $this->_deInit();
            return;
        }

        $this->_checkData();

        // Form has some errors, so end processing here
        if ($this->status->error) {
            $this->_deInit();
            return;
        }

        $this->_submit();

        $this->_deInit();
    }

    protected function _isSubmit()
    {
        if (is_string($this->submitButton)) {
            if ($this->dataIn->has(strval($this->submitButton))) {
                $this->mode = $this->submitButton;
                return true;
            }
        } else if (is_array($this->submitButton)) {
            foreach ($this->submitButton as $id) {
                if ($this->dataIn->has($id)) {
                    $this->mode = $id;
                    return true;
                }
            }
        }

        $button = $this->dataIn->get('CT_Form_SubmitButton');
        if ($button) {
            if ((is_string($this->submitButton) && $this->submitButton == $button) ||
                  is_array($this->submitButton) && in_array($button, $this->submitButton)
            ) {
                $this->mode = $button;
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    protected function _preInit()
    {
    }

    protected function _init()
    {
    }

    protected function _deInit()
    {
    }

    /** @return mixed */
    protected function _createDataObject()
    {
    }

    protected function _getData()
    {
    }

    protected function _checkData()
    {
        return null;
    }

    protected function _submit()
    {
    }

    protected function _finishForm()
    {
        $this->data = $this->_createDataObject();
    }

    public static function addDefaultSubmitButton($name)
    {
        return '<input type="hidden" name="CT_Form_SubmitButton" value="' . $name . '" />';
    }
}