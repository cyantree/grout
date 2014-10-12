<?php
namespace Cyantree\Grout\App\Generators\Template;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;

class TemplateGenerator
{
    /** @var App */
    public $app;

    /** @var Module */
    public $defaultModule;

    /** @var Plugin */
    public $defaultPlugin;

    /** @var TemplateContext */
    private $templateContext;

    public $baseTemplate = null;

    private function decodeName($name)
    {
        $context = AppTools::decodeContext(
            $name,
            $this->app,
            $this->defaultModule ? $this->defaultModule : $this->app->currentTask->module,
            $this->defaultPlugin ? $this->defaultPlugin : $this->app->currentTask->plugin
        );

        if ($context->plugin) {
            $template = $context->plugin->path . 'templates/' . $context->uri . '.php';

        } elseif ($context->module) {
            $template = $context->module->path . 'templates/' . $context->uri . '.php';

        } else {
            throw new \Exception('Context could not be resolved.');
        }

        return $template;
    }

    public function setTemplateContext(TemplateContext $context)
    {
        $context->generator = $this;
        $context->app = $this->app;

        $this->templateContext = $context;
    }

    public function load($name, $in = null, $baseTemplate = null)
    {
        $file = $this->decodeName($name);

        if (!$this->templateContext) {
            $this->setTemplateContext(new TemplateContext());
        }

        $c = clone $this->templateContext;

        $pos = strrpos($name, '/');
        if ($pos !== false) {
            $c->uriPath = substr($name, 0, $pos + 1);
            $c->uriName = substr($name, $pos);

        } else {
            $c->uriName = $name;
        }

        $c->task = $this->app->currentTask;
        $c->out = new ArrayFilter();
        $c->baseTemplate = $baseTemplate !== null ? $baseTemplate : $this->baseTemplate;
        $c->parse($file, $in);
        return $c;
    }
}
