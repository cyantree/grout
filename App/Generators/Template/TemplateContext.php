<?php
namespace Cyantree\Grout\App\Generators\Template;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Task;
use Cyantree\Grout\Filter\ArrayFilter;

class TemplateContext
{
    /** @var Task */
    public $task;

    /** @var App */
    public $app;

    public $uriPath;
    public $uriName;

    /** @var ArrayFilter */
    public $in;

    /** @var ArrayFilter */
    public $out;

    public $content;

    /** @var TemplateGenerator */
    public $generator;

    public $baseTemplate = null;

    public function parse($file, $in = null)
    {
        if (!$in || is_array($in)) {
            $this->in = new ArrayFilter($in);

        } else {
            $this->in = $in;
        }

        ob_start();

        include($file);

        $this->content = ob_get_clean();

        if ($this->baseTemplate) {
            $this->content = $this->generator->load($this->baseTemplate, array('content' => $this->content, 'data' => $this->out), false)->content;
        }
    }

    public function load($name, $in = null, $baseTemplate = false)
    {
        if (substr($name, 0, 2) == './') {
            $name = $this->uriPath . substr($name, 2);
        }

        return $this->generator->load($name, $in, $baseTemplate);
    }

    public function __toString()
    {
        return $this->content;
    }
}
