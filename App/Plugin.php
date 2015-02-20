<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

class Plugin extends Component
{
    public $id;

    // TODO: Deprecated
    public $type;

    /** @var Module */
    public $module;

    /** @var Plugin */
    public $parentPlugin;

    // TODO: Deprecated
    public $path;

    // TODO: Deprecated
    public $namespace;

    /** @var ArrayFilter */
    public $config;

    // TODO: Add asset url prefix

    public function init()
    {
    }

    public function addNamedRoute($id, $url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $p = $this->module->addNamedRoute($id, $url, $type, $data, $priority, $enabled);
        $p->plugin = $this;

        return $p;
    }

    public function addRoute($url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $p = $this->module->addRoute($url, $type, $data, $priority, $enabled);
        $p->plugin = $this;

        return $p;
    }

    /**
     * @param Task $task
     * @param Route $route
     */
    public function routeRetrieved($task, $route)
    {
        return true;
    }

    /** @param $task Task */
    public function initTask($task)
    {

    }

    /** @param $task Task */
    public function beforeParsing($task)
    {

    }

    /** @param $task Task */
    public function afterParsing($task)
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
        // TODO: Migrate to assetUrlPrefix
        return $this->module->getPublicAssetUrl($path, $absoluteURL, $parameters);
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
