<?php
namespace Cyantree\Grout\App;


use Cyantree\Grout\Quick;
use Cyantree\Grout\Tools\AppTools;

class GroutQuick extends Quick
{
    /** @var App */
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        parent::__construct();
    }

    public function eu($uri, $parameters = null, $absolute = true, $escapeContext = 'html')
    {
        return $this->e($this->u($uri, $parameters, $absolute), $escapeContext);
    }

    public function er($uri, $arguments = null, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->r($uri, $arguments, $parameters), $escapeContext);
    }

    public function ea($uri, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->a($uri, $parameters), $escapeContext);
    }

    public function u($uri, $parameters = null, $absolute = true)
    {
        $context = AppTools::decodeContext($uri, $this->app, $this->app->currentTask->module, $this->app->currentTask->plugin);

        if ($context->plugin) {
            return $context->plugin->getUrl($context->uri, $absolute, $parameters);

        } elseif ($context->module) {
            return $context->module->getUrl($context->uri, $absolute, $parameters);

        } else {
            throw new \Exception('URL could not be resolved.');
        }
    }

    public function r($uri, $arguments = null, $parameters = null, $absolute = true)
    {
        $context = AppTools::decodeContext($uri, $this->app, $this->app->currentTask->module, $this->app->currentTask->plugin);

        if ($context->plugin) {
            return $context->plugin->getRouteUrl($context->uri, $arguments, true, $parameters);

        } elseif ($context->module) {
            return $context->module->getRouteUrl($context->uri, $arguments, true, $parameters);

        } else {
            throw new \Exception('Route could not be resolved.');
        }
    }

    public function a($uri, $parameters = null, $absolute = false)
    {
        $context = AppTools::decodeContext(
                $uri,
                $this->app,
                $this->app->currentTask->module,
                $this->app->currentTask->plugin
        );


        if ($context->plugin) {
            return $context->plugin->getPublicAssetUrl($context->uri, $absolute, $parameters);

        } elseif ($context->module) {
            return $context->module->getPublicAssetUrl($context->uri, $absolute, $parameters);

        } else {
            return $context->app->getPublicAssetUrl($context->uri, $absolute, $parameters);
        }
    }
}
