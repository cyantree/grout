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
            $this->content = $this->generator->load(
                $this->baseTemplate,
                array('content' => $this->content, 'data' => $this->out),
                false
            )->content;
        }
    }

    public function load($name, $in = null, $baseTemplate = false)
    {
        if (strpos($name, ':') === false) {
            // TODO: Nicht sehr schön hier. Siehe auch exists()
            if (substr($name, 0, 1) != '/') {
                $name = $this->uriPath . $name;

            } else {
                $name = substr($name, 1);
            }
        }

        return $this->generator->load($name, $in, $baseTemplate);
    }

    public function exists($name)
    {
        if (strpos($name, ':') === false) {
            if (substr($name, 0, 1) != '/') {
                $name = $this->uriPath . $name;

            } else {
                $name = substr($name, 1);
            }
        }

        return $this->generator->exists($name);
    }

    public function __toString()
    {
        return $this->content;
    }
}
