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

    public function er($uri, $arguments = null, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->r($uri, $arguments, $parameters), $escapeContext);
    }

    public function ea($uri, $parameters = null, $escapeContext = 'html')
    {
        return $this->e($this->a($uri, $parameters), $escapeContext);
    }

    public function r($uri, $arguments = null, $parameters = null)
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
