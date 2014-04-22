<?php
namespace Cyantree\Grout\Form;

use Cyantree\Grout\Bucket\Bucket;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\StatusContainer;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class AdvancedForm
{
    const FLAG_ACTION_TIME = 1;
    const FLAG_JAVASCRIPT = 2;
    const FLAG_ALLOW_INSTANT_SUBMIT = 4;
    const FLAG_CONTEXT_NO_IP = 8;

    const ERROR_EARLY = 1;
    const ERROR_DELETED = 2;

    public static $earlyTimeout = 2;

    public static $defaultFlags = 0;

    public static $MESSAGE_ERROR_EARLY = 'Please wait a moment before submitting this form.';
    public static $MESSAGE_ERROR_DELETED = 'Your inquiry has expired.';

    /** @var \Cyantree\Grout\Bucket\Bucket */
    public static $defaultBucketBase;

    public $id;
    public $steps = 2;

    /** @var AdvancedFormData */
    public $formData;

    /** @var ArrayFilter */
    public $dataIn;

    public $data;

    /** @var ArrayFilter */
    public $dataFiles;

    public $flags = -1;

    public $submitButton = array('submit' => 'next', 'prev' => 'prev', 'next' => 'next');
    public $mode;
    public $action;
    public $isSubmit;

    /** @var \Cyantree\Grout\Bucket\Bucket */
    public $bucketBase;

    /** @var \Cyantree\Grout\Bucket\Bucket */
    private $_bucket;

    /** @var StatusContainer */
    public $status;

    private $_internalID;

    public function getDataIn()
    {
        return new ArrayFilter($_POST);
    }

    public function getContext()
    {
        if ($this->flags & self::FLAG_CONTEXT_NO_IP) {
            return $this->id;
        } else {
            return $this->id . $_SERVER['REMOTE_ADDR'];
        }
    }

    public function execute()
    {
        // PreInit form
        $this->_preInit();

        if ($this->flags == -1) {
            $this->flags = self::$defaultFlags;
        }

        $this->status = new StatusContainer();

        if (!$this->id) {
            $this->id = get_class($this);
        }
        $this->_internalID = substr(md5($this->id), 0, 8);

        $this->dataIn = $this->getDataIn();

        // Check for form id
        $requestedID = $this->dataIn->get($this->_internalID . '_BucketID');

        $isNew = !$requestedID;

        if (!$this->bucketBase) {
            if (!AdvancedForm::$defaultBucketBase) {
                trigger_error('Could not find valid bucket base. Specify with $form->bucketBase set AdvancedForm::$defaultBucketBase', E_USER_ERROR);
            }
            $this->bucketBase = AdvancedForm::$defaultBucketBase;
        }

        $context = $this->getContext();

        if ($this->status->error) {
            $this->_endProcessing();
            return;
        }

        // Form id existing, try to load bucket
        if (!$isNew) {
            $this->_bucket = $this->bucketBase->load($requestedID, $context);

            // No valid bucket found
            if (!$this->_bucket) {
                $isNew = true;
                $this->_processSecurityError(self::ERROR_DELETED);
            } else {
                $this->formData = $this->_bucket->data;

                $this->data = $this->formData->data;

                // Bucket is valid but doesn't fit to this form
                if ($this->formData->id != $this->dataIn->get('CT_Form_ID')) {
                    $isNew = true;
                    $this->_processSecurityError(self::ERROR_DELETED);
                    $this->_bucket = null;
                } else if ($this->formData->resetOnNextAccess) {
                    $this->formData->reset();
                    $this->formData->data = $this->data = $this->_createDataObject();
                }

                if ($this->formData->finished) {
                    $this->formData->finished = false;
                }
            }
        }

        // New form, create data object
        if ($isNew) {
            $this->data = $this->_createDataObject();
            $uID = StringTools::random(32);

            $this->formData = new AdvancedFormData($uID, $this->data);
            $this->_bucket = $this->bucketBase->create('', 86400, $context);
        }

        $this->isSubmit = $this->_isSubmit();

        // Init form
        $this->_init();

        // Form hasn't been submitted yet or an error has occurred, so end processing here
        if (!$this->isSubmit || ((!$this->flags & self::FLAG_ALLOW_INSTANT_SUBMIT) && $isNew) || $this->status->error) {
            $this->_endProcessing();
            return;
        }


        // Get submitted step
        $step = intval($this->dataIn->get('CT_Form_Step'));
        if ($step < 1 && ($this->flags & self::FLAG_ALLOW_INSTANT_SUBMIT)) {
            $step = 1;
        }

        if ($step < 1) {
            $this->_endProcessing();
            return;
        }

        if ($step > $this->formData->currentStep) {
            $this->_endProcessing();
            return;
        }

        $this->_getStepData($step);

        // Check whether earlier step is requested
        if (is_int($this->action)) {
            $this->formData->nextStep = $this->action;
            if ($this->formData->nextStep > 0 && $this->formData->nextStep <= $this->formData->currentStep) {
                $this->formData->currentStep = $this->formData->nextStep;
                $this->_endProcessing();
                return;
            } else {
                $this->action = 'next';
            }
        }

        if (!$this->action) {
            $this->action = 'next';
        }

        // Calculate next step
        if ($this->action == 'next') {
            $this->formData->nextStep = $this->_getNextStep($step);
        } else if ($this->action == 'prev') {
            $this->formData->nextStep = $this->_getPrevStep($step);
        } else {
            $this->formData->nextStep = $step;
        }

        if ($this->formData->nextStep < 1) {
            $this->formData->nextStep = 1;
        } else if ($this->formData->nextStep > $this->steps) {
            $this->formData->nextStep = $this->steps;
        }

        // Let's check some security stuff
        if (($this->flags & self::FLAG_ACTION_TIME) && (!$this->flags & self::FLAG_ALLOW_INSTANT_SUBMIT)) {
            if (microtime(true) - $this->formData->lastAction < self::$earlyTimeout) {
                $this->_processSecurityError(self::ERROR_EARLY);
            }
        }

        if ($this->action == 'prev') {
            $this->formData->currentStep = $this->formData->nextStep;
            $this->_endProcessing();
            return;
        }

        if ($this->status->error || ($this->steps > 1 && $step == $this->steps)) {
            $this->_endProcessing();
            return;
        }

        $this->_checkStepData($step);

        // Form has some errors, so end processing here
        if ($this->status->error) {
            $this->_endProcessing();
            return;
        }

        $this->_submitStep($step);

        // Processing successful so set next step
        if (!$this->status->error && $this->steps > 1) {
            $this->formData->currentStep = $this->formData->nextStep;
        }

        $this->_endProcessing();
    }

    protected function _getNextStep($lastStep)
    {
        return $lastStep + 1;
    }

    protected function _getPrevStep($lastStep)
    {
        return $lastStep - 1;
    }

    protected function _isSubmit()
    {
        if (is_string($this->submitButton)) {
            if ($this->dataIn->has(strval($this->submitButton))) {
                $this->mode = $this->submitButton;
                $this->action = 'next';
                return true;
            }
        } else if (is_array($this->submitButton)) {
            foreach ($this->submitButton as $id => $action) {
                if ($this->dataIn->has($id)) {
                    $this->mode = $id;
                    $this->action = $action;
                    return true;
                }
            }
        }

        for ($i = 1; $i <= $this->steps; $i++) {
            if ($this->dataIn->has('step' . $i)) {
                $this->mode = 'step' . $i;
                $this->action = $i;
                return true;
            }
        }

        $button = $this->dataIn->get('CT_Form_SubmitButton');
        if ($button) {
            $this->mode = $button;
            if (is_array($this->submitButton)) {
                $this->action = ArrayTools::get($this->submitButton, $button);
            } else if ($button == $this->submitButton) {
                $this->action = 'next';
            }

            if ($this->action) {
                return true;
            }

            if (!$this->action) {
                for ($i = 1; $i <= $this->steps; $i++) {
                    if ($button == 'step' . $i) {
                        $this->action = $i;
                        return true;
                    }
                }
            }

            return false;
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
        return null;
    }

    protected function _getStepData($step)
    {
    }

    protected function _checkStepData($step)
    {
    }

    protected function _submitStep($step)
    {
    }

    protected function _finishForm($resetData = false)
    {
        $this->formData->id = StringTools::random(32);

        if ($resetData) {
            $this->formData->reset();
            $this->formData->data = $this->data = $this->_createDataObject();
        } else {
            $this->formData->resetOnNextAccess = true;
        }

        $this->formData->finished = true;
    }

    protected function _processSecurityError($id)
    {
        if ($id == self::ERROR_DELETED) {
            $this->status->addError('GroutFormSecurity', self::$MESSAGE_ERROR_DELETED);
        } else if ($id == self::ERROR_EARLY) {
            $this->status->addError('GroutFormSecurity', self::$MESSAGE_ERROR_EARLY);
        }
    }

    protected function _endProcessing()
    {
        if ($this->_bucket) {
            $this->formData->lastAction = microtime(true);
            $this->_bucket->data = $this->formData;
            $this->_bucket->save();
        }

        $this->_deInit();
    }

    public function show()
    {
        $bucketIDName = $this->_internalID . '_BucketID';
        if (!$this->formData) {
            return '';
        }
        if ($this->flags & self::FLAG_JAVASCRIPT) {
            $js = '<script type="text/javascript">document.write("<"+"input type=\"hidden\" name\="CT_Form_ID\" value=\"' . $this->formData->id . '" />"+' .
                  '"<"+"input type=\"hidden\" name=\"' . $bucketIDName . '\" value=\"' . $this->_bucket->id . '\" />"+' .
                  '"<"+"input type=\"hidden\" name=\"CT_Form_Step\" value=\"' . $this->formData->currentStep . '" />");</script>';

            return $js;
        }

        return '<input type="hidden" name="CT_Form_ID" value="' . $this->formData->id . '" />' .
        '<input type="hidden" name="' . $bucketIDName . '" value="' . $this->_bucket->id . '" />' .
        '<input type="hidden" name="CT_Form_Step" value="' . $this->formData->currentStep . '" />';
    }

    public static function addDefaultSubmitButton($name)
    {
        return '<input type="hidden" name="CT_Form_SubmitButton" value="' . $name . '" />';
    }
}