<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

class Plugin extends Component
{
    public $id;

    /** @var Module */
    public $module;

    /** @var ArrayFilter */
    public $config;

    public $assetUrlPrefix;

    public function init()
    {
    }

    private function calculateRoutePriority($routePriority)
    {
        $routePriority = min(max($routePriority, -49), 49);

        return $this->priority * 100 + $routePriority + 50;
    }

    public function addNamedRoute($id, $url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $p = $this->module->addNamedRoute($id, $url, $type, $data, $this->calculateRoutePriority($priority), $enabled);
        $p->plugin = $this;

        return $p;
    }

    public function addRoute($url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $p = $this->module->addRoute($url, $type, $data, $this->calculateRoutePriority($priority), $enabled);
        $p->plugin = $this;

        return $p;
    }

    public function routeRetrieved(Task $task, Route $route)
    {
        return true;
    }

    public function initTask(Task $task)
    {

    }

    public function beforeParsing(Task $task)
    {

    }

    public function afterParsing(Task $task)
    {

    }

    public function getRoute($id)
    {
        return $this->module->getRoute($id);
    }

    public function getPublicUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->module->getPublicUrl($path, $absoluteURL, $parameters);
    }

    public function getPublicAssetUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->app->getPublicAssetUrl($this->assetUrlPrefix . $path, $absoluteURL, $parameters);
    }

    public function getUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->module->getUrl($path, $absoluteURL, $parameters);
    }

    public function getRouteUrl($id, $arguments = null, $absoluteURL = true, $parameters = null)
    {
        return $this->module->getRouteUrl($id, $arguments, $absoluteURL, $parameters);
    }

    public function hasRoute($route)
    {
        return $this->module->hasRoute($route);
    }

    public function generateContextString($uri)
    {
        return '#' . $this->module->id . ':#' . $this->id . ':' . $uri;
    }
}
