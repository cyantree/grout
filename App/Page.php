<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Task;
use Cyantree\Grout\App\Types\ContentType;

class Page
{
    /** @var Task */
    public $task;

    /** @var App */
    public $app;

    /** @var Module */
    public $module;

    /** @var Plugin */
    public $plugin;


    public $data = array();

    public function parseTask()
    {
    }

    public function request()
    {
        return $this->task->request;
    }

    public function response()
    {
        return $this->task->response;
    }

    public function setResult($content, $contentType = null, $responseCode = null)
    {
        if (!is_string($content)) {
            $content = strval($content);
        }

        if ($responseCode) {
            $this->task->response->code = $responseCode;
        }

        $this->task->response->postContent($content, $contentType);
    }

    public function parseError($code, $data = null)
    {
        if ($code == 403) {
            if ($this->task->plugin && $this->task->plugin->hasRoute('GroutError403')) {
                $this->task->redirectToRoute($this->task->plugin->getRoute('GroutError403'));

            } elseif ($this->task->module && $this->task->module->hasRoute('GroutError403')) {
                $this->task->redirectToRoute($this->task->module->getRoute('GroutError403'));

            } else {
                $this->setResult('You are not allowed to access this page.', ContentType::TYPE_PLAIN_UTF8, 403);
            }

        } elseif ($code == 404) {
            if ($this->task->plugin && $this->task->plugin->hasRoute('GroutError404')) {
                $this->task->redirectToRoute($this->task->plugin->getRoute('GroutError404'));

            } elseif ($this->task->module && $this->task->module->hasRoute('GroutError404')) {
                $this->task->redirectToRoute($this->task->module->getRoute('GroutError404'));

            } else {
                $this->setResult('The requested page does not exist.', ContentType::TYPE_PLAIN_UTF8, 404);
            }

        } else {
            if ($this->task->plugin && $this->task->plugin->hasRoute('GroutError500')) {
                $this->task->redirectToRoute($this->task->plugin->getRoute('GroutError500'));

            } elseif ($this->task->module && $this->task->module->hasRoute('GroutError500')) {
                $this->task->redirectToRoute($this->task->module->getRoute('GroutError500'));

            } else {
                $this->setResult('An unknown error has occurred.', ContentType::TYPE_PLAIN_UTF8, 500);
            }
        }
    }

    public function beforeParsing()
    {
    }

    public function afterParsing()
    {
    }
}
