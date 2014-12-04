<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Status\Status;
use Cyantree\Grout\Status\StatusContainer;

abstract class Content
{
    /** @var Set */
    public $set;

    public $errorMessages = array();
    public $warningMessages = array();
    public $infoMessages = array();
    public $successMessages = array();

    public $name;

    public $guid;
    public $storeInSet = true;
    public $enabled = true;
    public $render = true;

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

    protected $value;

    public function __construct()
    {
        $this->config = new ArrayFilter();
    }

    public function init()
    {
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

    protected function getWarningMessage($code)
    {
        if (isset($this->warningMessages[$code])) {
            return $this->warningMessages[$code];
        }

        return $this->getDefaultWarningMessage($code);
    }

    protected function getDefaultWarningMessage($code)
    {
        return '';
    }

    protected function getRenderer()
    {
        return $this->set->contentRenderers->getContentRenderer(get_class($this));
    }

    public function setValue($data)
    {
        $this->value = $data;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function onLoaded()
    {
    }

    public function onSaved()
    {
    }

    public function render()
    {
        if ($this->renderer === null) {
            $this->renderer = $this->getRenderer();
        }

        if (!$this->renderer) {
            throw new \Exception('No renderer found for content ' . get_class($this));
        }

        return $this->renderer->render($this);
    }

    /**
     * @param $data ArrayFilter
     * @param $files ArrayFilter
     */
    public function populate($data, $files)
    {
        $this->value = $data->get($this->name);
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
        return $this->set->status->error->has($this->name . '.' . $code);
    }

    public function hasErrors()
    {
        return $this->set->status->error->has($this->name);
    }

    public function postError($code, $messageReplaces = null, $message = null)
    {
        if ($message === null) {
            $message = $this->getErrorMessage($code);
        }

        $this->addStatus($this->set->status->error, $code, $message, $messageReplaces);
    }

    public function postWarning($code, $messageReplaces = null, $message = null)
    {
        if ($message === null) {
            $message = $this->getWarningMessage($code);
        }

        $this->addStatus($this->set->status->warning, $code, $message, $messageReplaces);
    }

    public function postInfo($code, $messageReplaces = null, $message = null)
    {
        if ($message === null) {
            $message = $this->getInfoMessage($code);
        }
        
        $this->addStatus($this->set->status->info, $code, $message, $messageReplaces);
    }

    public function postSuccess($code, $messageReplaces = null, $message = null)
    {
        if ($message === null) {
            $message = $this->getSuccessMessage($code);
        }
        
        $this->addStatus($this->set->status->success, $code, $message, $messageReplaces);
    }

    private function addStatus(StatusContainer $container, $code, $message, $messageReplaces = null)
    {
        if ($message) {
            if ($messageReplaces === null) {
                $messageReplaces = array();
            }
            $messageReplaces['%name%'] = $this->config->get('label');
        }

        $status = new Status();
        $status->code = $this->name . '.' . $code;
        $status->message = $message;
        $status->replaces = $messageReplaces;

        $container->addManual($this->name);
        $container->add($status);
    }
}
