<?php
namespace Cyantree\Grout\Set;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\ContentRendererProvider\DefaultContentRendererProvider;
use Cyantree\Grout\Status\StatusBag;

abstract class Set
{
    const MODE_LIST = 'list';
    const MODE_ADD = 'add';
    const MODE_EDIT = 'edit';
    const MODE_DELETE = 'delete';
    const MODE_SHOW = 'show';
    const MODE_EXPORT = 'export';

    const FORMAT_HTML = 'html';
    const FORMAT_PLAIN = 'plain';
    const FORMAT_SERIALIZABLE = 'serializable';

    public $allowList = true;
    public $allowShow = true;
    public $allowEdit = true;
    public $allowAdd = true;
    public $allowDelete = true;
    public $allowExport = true;

    /** @var Content[] */
    public $contents = array();

    /** @var Content */
    public $firstContent = null;

    /** @var Content */
    public $lastContent = null;

    /** @var Content */
    public $idContent = null;

    /** @var ArrayFilter */
    public $config;

    /** @var StatusBag */
    public $status;

    public $mode;
    public $context;
    public $format;

    /** @var DefaultContentRendererProvider */
    public $contentRenderers;

    public function __construct()
    {
        $this->config = new ArrayFilter();
        $this->status = new StatusBag();
    }

    public function init($mode, $format = null, $context = null)
    {
        $this->mode = $mode;
        $this->format = $format;
        $this->context = $context;

        $this->contentRenderers = new DefaultContentRendererProvider();
        $this->contentRenderers->set = $this;

        $this->setup();
    }

    abstract public function setup();

    public function onList($elements)
    {

    }

    public function getId()
    {
        return null;
    }

    public function setId($id)
    {
        if ($this->idContent !== null) {
            $this->idContent->setValue($id);
        }
    }

    /** @param $content Content */
    public function appendContent($content)
    {
        $content->set = $this;

        if ($this->lastContent) {
            $this->lastContent->nextContent = $content;
            $content->previousContent = $this->lastContent;
        } else {
            $this->firstContent = $content;
        }

        $this->lastContent = $content;
        $this->contents[$content->name] = $content;

        if ($content->enabled) {
            $content->init($this->mode, $this->format, $this->context);
        }
    }

    /** @param $content Content */
    public function prependContent($content)
    {
        $content->set = $this;

        if ($this->firstContent) {
            $this->firstContent->previousContent = $content;
            $content->nextContent = $this->firstContent;

        } else {
            $this->lastContent = $content;
        }

        $this->firstContent = $content;
        $this->contents[$content->name] = $content;

        if ($content->enabled) {
            $content->init($this->mode, $this->format, $this->context);
        }
    }

    /** @param $content Content */
    public function addContentAfter($content, $previousContentId)
    {
        $content->set = $this;

        $otherContent = $this->contents[$previousContentId];

        if ($otherContent->nextContent) {
            $otherContent->nextContent->previousContent = $content;
            $content->nextContent = $otherContent->nextContent;
        } else {
            $this->lastContent = $content;
        }

        $otherContent->nextContent = $content;
        $content->previousContent = $otherContent;

        $this->contents[$content->name] = $content;

        if ($content->enabled) {
            $content->init($this->mode, $this->format, $this->context);
        }
    }

    /** @param $content Content */
    public function addContentBefore($content, $previousContentId)
    {
        $content->set = $this;

        $otherContent = $this->contents[$previousContentId];

        if ($otherContent->previousContent) {
            $otherContent->previousContent->nextContent = $content;
            $content->previousContent = $otherContent->previousContent;
        } else {
            $this->firstContent = $content;
        }

        $otherContent->previousContent = $content;
        $content->nextContent = $otherContent;

        $this->contents[$content->name] = $content;

        if ($content->enabled) {
            $content->init($this->mode, $this->format, $this->context);
        }
    }

    public function removeContent(Content $content)
    {
        $previousContent = $content->previousContent;
        $nextContent = $content->nextContent;

        if ($previousContent) {
            $previousContent->nextContent = $nextContent;

        } else {
            $this->firstContent = $nextContent;
        }

        if ($nextContent) {
            $nextContent->previousContent = $previousContent;

            if (!$nextContent->nextContent) {
                $this->lastContent = $nextContent->nextContent;
            }

        } else {
            $this->lastContent = $previousContent;
        }

        $content->previousContent = $content->nextContent = null;
        $content->set = null;

        unset($this->contents[$content->name]);
    }

    public function replaceContentByName($name, Content $newContent)
    {
        $currentContent = $this->getContentByName($name);

        $this->replaceContent($currentContent, $newContent);
    }

    public function replaceContent(Content $oldContent, Content $newContent)
    {
        $newContent->name = $oldContent->name;
        $this->contents[$oldContent->name] = $newContent;

        $newContent->previousContent = $oldContent->previousContent;
        $newContent->nextContent = $oldContent->nextContent;

        if ($newContent->previousContent) {
            $newContent->previousContent->nextContent = $newContent;
        }

        if ($newContent->nextContent) {
            $newContent->nextContent->previousContent = $newContent;
        }

        if ($this->firstContent == $oldContent) {
            $this->firstContent = $newContent;
        }

        if ($this->lastContent == $oldContent) {
            $this->lastContent = $newContent;
        }

        $newContent->set = $this;

        if ($newContent->enabled) {
            $newContent->init($this->mode, $this->format, $this->context);
        }
    }

    /** @return Content */
    public function getContentByName($name)
    {
        if (isset($this->contents[$name])) {
            return $this->contents[$name];
        }

        throw new \Exception('Content ' . $name . ' does not exist.');
    }

    public function createNew()
    {
        foreach ($this->contents as $name => $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->reset();
        }
    }

    public function loadById($id)
    {
        return null;
    }

    public function getValues()
    {
        $a = array();

        foreach ($this->contents as $name => $content) {
            if (!$content->enabled) {
                continue;
            }

            $a[$name] = $content->getValue();
        }

        return $a;
    }

    public function setValues($values)
    {
        foreach ($this->contents as $name => $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->setValue($values[$name]);
        }
    }

    public function getValueByName($name)
    {
        return $this->getContentByName($name)->getValue();
    }

    public function setValueByName($name, $value)
    {
        $this->getContentByName($name)->setValue($value);
    }

    public function populate($rawData, $rawFiles = null)
    {
        $d = new ArrayFilter($rawData);
        $files = new ArrayFilter($rawFiles);

        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->editable) {
                $content->populate($d, $files);
            }
        }
    }

    public function check()
    {
        $this->status->reset();

        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->editable) {
                $content->check();
            }
        }
    }

    public function save()
    {
        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->editable) {
                $content->save();
            }
        }

        $this->collectData();

        $this->doSave();

        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            if ($content->editable) {
                $content->onSaved();
            }
        }
    }

    protected function collectData()
    {

    }

    protected function doSave()
    {

    }

    public function setContentConfig($config, $value, $contentNames = null)
    {
        if ($contentNames) {
            if (is_array($contentNames)) {
                foreach ($contentNames as $contentName) {
                    $this->contents[$contentName]->config->set($config, $value);
                }
            } else {
                $this->contents[$contentNames]->config->set($config, $value);
            }
        } else {
            foreach ($this->contents as $content) {
                $content->config->set($config, $value);
            }
        }
    }

    public function setContentProperty($property, $value, $contentNames = null)
    {
        if ($contentNames) {
            if (is_array($contentNames)) {
                foreach ($contentNames as $contentName) {
                    $this->contents[$contentName]->{$property} = $value;
                }
            } else {
                $this->contents[$contentNames]->{$property} = $value;
            }
        } else {
            foreach ($this->contents as $content) {
                $content->{$property} = $value;
            }
        }
    }

    public function render($nameOrContent)
    {
        if (is_object($nameOrContent)) {
            return $nameOrContent->render();
        }

        return $this->contents[$nameOrContent]->render();
    }

    public function renderToArray()
    {
        $d = array();

        $content = $this->firstContent;

        do {
            if ($content->enabled && $content->render) {
                $d[$content->name] = $content->render();
            }
        } while ($content = $content->nextContent);

        return $d;
    }

    protected function onLoaded()
    {
        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->onLoaded();
        }
    }

    public function delete()
    {
        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->prepareDelete();
        }
        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->onDelete();
        }

        $this->doDelete();

        foreach ($this->contents as $content) {
            if (!$content->enabled) {
                continue;
            }

            $content->onDeleted();
        }

        // TODO: Remove return or use it
        return true;
    }

    protected function doDelete()
    {

    }

    public function postError($code, $message = null, $messageReplaces = null)
    {
        $this->status->error->addManual($code, $message, $messageReplaces);
    }

    public function postInfo($code, $message = null, $messageReplaces = null)
    {
        $this->status->info->addManual($code, $message, $messageReplaces);
    }

    public function postSuccess($code, $message = null, $messageReplaces = null)
    {
        $this->status->success->addManual($code, $message, $messageReplaces);
    }
    
    public function postWarning($code, $message = null, $messageReplaces = null)
    {
        $this->status->warning->addManual($code, $message, $messageReplaces);
    }

    /** @return SetListResult */
    abstract public function listSets($options);

    /** @return SetTypeCapabilities */
    public function getCapabilities()
    {
        /** @var SetTypeCapabilities $c */
        $c = null;
        return $c;
    }
}
