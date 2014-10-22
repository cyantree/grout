<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;

abstract class Content
{
    /** @var Set */
    public $set;

    public $errorMessages = array();
    public $infoMessages = array();
    public $successMessages = array();

    public $name;

    public $guid;
    public $storeInSet = true;
    public $visible = true;

    public $searchable = false;
    public $sortable = false;
    public $editable = true;

    public $required = false;

    /** @var ArrayFilter */
    public $config;

    /** @var Content  */
    public $nextContent = null;

    /** @var Content  */
    public $previousContent = null;

    /** @var ContentRenderer */
    public $renderer;

    protected $mode;

    protected $data;

    public function __construct()
    {
        $this->config = new ArrayFilter(array('visible' => true));
    }

    final public function init()
    {
        $this->onInit();
    }

    protected function getErrorMessage($code)
    {
        if (isset($this->errorMessages[$code])) {
            return $this->errorMessages[$code];
        }

        return $this->getDefaultErrorMessage($code);
    }

    protected function getDefaultErrorMessage($code)
    {
        return '';
    }

    protected function getInfoMessage($code)
    {
        if (isset($this->infoMessages[$code])) {
            return $this->infoMessages[$code];
        }

        return $this->getDefaultInfoMessage($code);
    }

    protected function getDefaultInfoMessage($code)
    {
        return '';
    }

    protected function getSuccessMessage($code)
    {
        if (isset($this->successMessages[$code])) {
            return $this->successMessages[$code];
        }

        return $this->getDefaultSuccessMessage($code);
    }

    protected function getDefaultSuccessMessage($code)
    {
        return '';
    }

    protected function onInit()
    {

    }

    abstract protected function getDefaultRenderer();

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function onLoaded()
    {
    }

    public function onSaved()
    {
    }

    public function render()
    {
        return $this->renderer->render($this, $this->mode);
    }

    public function prepareRendering($mode)
    {
        $this->mode = $mode;

        if ($this->renderer === null) {
            $this->renderer = $this->getDefaultRenderer();
        }
    }

    /**
     * @param $data ArrayFilter
     * @param $files ArrayFilter
     */
    public function populate($data, $files)
    {
        $this->data = $data->get($this->name);
    }

    public function check()
    {
        return true;
    }

    public function save()
    {
    }

    public function prepareDelete()
    {
    }

    public function onDelete()
    {
    }

    public function onDeleted()
    {
    }

    public function hasError($code)
    {
        return $this->set->status->hasError($this->name . '.' . $code);
    }

    public function hasErrors()
    {
        return $this->set->status->hasError($this->name);
    }

    public function postError($code, $messageReplaces = null, $message = null)
    {
        $this->set->status->addError($this->name);

        $m = $this->prepareSetMessage($code, $message ? $message : $this->getErrorMessage($code), $messageReplaces);
        $this->set->status->addError($this->name . '.' . $code, $m);
    }

    public function postInfo($code, $messageReplaces = null, $message = null)
    {
        $this->set->status->addInfo($this->name);

        $m = $this->prepareSetMessage($code, $message ? $message : $this->getInfoMessage($code), $messageReplaces);
        $this->set->status->addInfo($this->name . '.' . $code, $m);
    }

    public function postSuccess($code, $messageReplaces = null, $message = null)
    {
        $this->set->status->addSuccess($this->name);

        $m = $this->prepareSetMessage($code, $message ? $message : $this->getSuccessMessage($code), $messageReplaces);
        $this->set->status->addSuccess($this->name . '.' . $code, $m);
    }
    
    private function prepareSetMessage($code, $message, $messageReplaces = null)
    {
        if ($message) {
            if ($messageReplaces === null) {
                $messageReplaces = array();
            }
            $messageReplaces['%name%'] = $this->config->get('label');
        }

        $m = new SetMessage();
        $m->content = $this;
        $m->code = $this->name . '.' . $code;
        $m->message = $message;
        $m->values = $messageReplaces;
        
        return $m;
    }
}
