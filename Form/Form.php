<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Status\StatusBag;

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

    /** @var StatusBag */
    public $status;

    public function getDataIn()
    {
        return new ArrayFilter($_POST);
    }

    public function execute()
    {
        // PreInit form
        $this->preInit();

        $this->status = new StatusBag();

        $this->dataIn = $this->getDataIn();

        if ($this->status->error->hasStatuses) {
            $this->deInit();
            return;
        }

        // New form, create data object
        $this->data = $this->createDataObject();

        $this->isSubmit = $this->isSubmit();

        // Init form
        $this->init();

        // Form hasn't been submitted yet or an error has occurred, so end processing here
        if (!$this->isSubmit || $this->status->error->hasStatuses) {
            $this->deInit();
            return;
        }

        $this->getData();

        if ($this->status->error->hasStatuses) {
            $this->deInit();
            return;
        }

        $this->checkData();

        // Form has some errors, so end processing here
        if ($this->status->error->hasStatuses) {
            $this->deInit();
            return;
        }

        $this->submit();

        $this->deInit();
    }

    protected function isSubmit()
    {
        if (is_string($this->submitButton)) {
            if ($this->dataIn->has(strval($this->submitButton))) {
                $this->mode = $this->submitButton;
                return true;
            }
        } elseif (is_array($this->submitButton)) {
            foreach ($this->submitButton as $id) {
                if ($this->dataIn->has($id)) {
                    $this->mode = $id;
                    return true;
                }
            }
        }

        $button = $this->dataIn->get('CT_Form_SubmitButton');
        if ($button) {
            if ((is_string($this->submitButton) && $this->submitButton == $button)
                || is_array($this->submitButton) && in_array($button, $this->submitButton)
            ) {
                $this->mode = $button;
                return true;

            } else {
                return false;
            }
        }

        return false;
    }

    protected function preInit()
    {
    }

    protected function init()
    {
    }

    protected function deInit()
    {
    }

    /** @return mixed */
    protected function createDataObject()
    {
        return null;
    }

    protected function getData()
    {
    }

    protected function checkData()
    {
        return null;
    }

    protected function submit()
    {
    }

    protected function finishForm()
    {
        $this->data = $this->createDataObject();
    }

    public static function addDefaultSubmitButton($name)
    {
        return '<input type="hidden" name="CT_Form_SubmitButton" value="' . $name . '" />';
    }
}
