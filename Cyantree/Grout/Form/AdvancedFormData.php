<?php
namespace Cyantree\Grout\Form;

class AdvancedFormData
{
    public $id;

    public $currentStep = 1;
    public $nextStep;

    public $resetOnNextAccess = false;
    public $finished = false;

    public $lastAction;

    public $data;

    public function __construct($id, $data)
    {
        $this->id = $id;
        $this->data = $data;
        $this->lastAction = microtime(true);
    }

    public function reset()
    {
        $this->currentStep = $this->nextStep = 1;
        $this->data = null;
        $this->finished = false;
        $this->resetOnNextAccess = false;
    }
}