<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;

abstract class Content
{
    /** @var Set */
    public $set;

    public $name;

    public $guid;
    public $storeInSet = true;

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

    public function postError($code, $message = null, $messageReplaces = null)
    {
        $this->set->status->addError($this->name);

        if ($message) {
            if ($messageReplaces === null) {
                $messageReplaces = array();
            }
            $messageReplaces['%name%'] = $this->config->get('label');

//            $message = str_replace(array_keys($messageReplaces), array_values($messageReplaces), $message);
        }

        $m = new SetMessage();
        $m->content = $this;
        $m->code = $this->name . '.' . $code;
        $m->message = $message;
        $m->values = $messageReplaces;

        $this->set->status->addError($this->name . '.' . $code, $m);
//        $this->set->status->postError($this->name.'.'.$code, $message);
    }

    public function postInfo($code, $message = null, $messageReplaces = null)
    {
        $this->set->status->addInfo($this->name);

        if ($message) {
            if ($messageReplaces === null) {
                $messageReplaces = array();
            }
            $messageReplaces['%name%'] = $this->config->get('label');

//            $message = str_replace(array_keys($messageReplaces), array_values($messageReplaces), $message);
        }

        $m = new SetMessage();
        $m->content = $this;
        $m->code = $this->name . '.' . $code;
        $m->message = $message;
        $m->values = $messageReplaces;

        $this->set->status->addInfo($this->name . '.' . $code, $m);
//        $this->set->status->postInfo($this->name.'.'.$code, $message);
    }

    public function postSuccess($code, $message = null, $messageReplaces = null)
    {
        $this->set->status->addSuccess($this->name);

        if ($message) {
            if ($messageReplaces === null) {
                $messageReplaces = array();
            }
            $messageReplaces['%name%'] = $this->config->get('label');

//            $message = str_replace(array_keys($messageReplaces), array_values($messageReplaces), $message);
        }

        $m = new SetMessage();
        $m->content = $this;
        $m->code = $this->name . '.' . $code;
        $m->message = $message;
        $m->values = $messageReplaces;

        $this->set->status->addSuccess($this->name . '.' . $code, $m);
//        $this->set->status->postSuccess($this->name.'.'.$code, $message);
    }
}
