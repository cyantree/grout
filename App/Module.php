<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Types\ResponseCode;
use Cyantree\Grout\AutoLoader;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\NamespaceTools;
use Cyantree\Grout\Tools\StringTools;

class Module extends Component
{
    public $id;

    public $defaultPageType;

    /** @var Plugin[] */
    public $plugins = array();

    /** @var Plugin[] */
    public $pluginTypes = array();

    /** @var Plugin[] */
    public $pluginIds = array();

    public $initiated = false;

    /** @var Route[] */
    public $routes = array();
    public $routesChanged = true;
    public $routesEnabled = true;

    public $urlPrefix;
    public $assetUrlPrefix;

    /** @var ArrayFilter */
    public $config;

    /** @var Events */
    public $events;

    public function __construct()
    {
        $this->events = new Events();
    }

    public function init()
    {
    }

    public function addNamedRoute($id, $url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $this->routesChanged = true;

        $p = new Route($url, $data, $priority);
        $p->id = $id;
        $p->page = $type ? $type : $this->defaultPageType;
        $p->module = $this;
        $p->enabled = $enabled;
        $this->routes[$id] = $p;

        $p->init();

        return $p;
    }

    public function addRoute($url, $type = null, $data = null, $priority = 0, $enabled = true)
    {
        $this->routesChanged = true;

        $p = new Route($url, $data, $priority);
        $p->page = $type ? $type : $this->defaultPageType;
        $p->module = $this;
        $p->enabled = $enabled;
        $this->routes[] = $p;

        $p->init();

        return $p;
    }

    public function getRoute($id, $needed = true)
    {
        if (isset($this->routes[$id])) {
            return $this->routes[$id];

        } elseif ($needed) {
            throw new \Exception('Route "' . $id . '" was not found in module "' . get_class($this) . '"');
        }

        return null;
    }

    public function routeRetrieved(Task $task, Route $route)
    {
        return true;
    }

    public function beforeParsing(Task $task)
    {
    }

    public function initTask(Task $task)
    {
    }

    public function destroyTask(Task $task)
    {
    }

    /** @param Task $task */
    public function afterParsing($task)
    {
    }

    public function destroy()
    {
    }

    public function getPublicUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->app->getPublicUrl($this->urlPrefix . $path, $absoluteURL, $parameters);
    }

    public function getPublicAssetUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->app->getPublicAssetUrl($this->assetUrlPrefix . $path, $absoluteURL, $parameters);
    }

    public function getUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        return $this->app->getUrl($this->urlPrefix . $path, $absoluteURL, $parameters);
    }

    public function getRouteUrl($id, $arguments = null, $absoluteURL = true, $parameters = null, $escapeArguments = true)
    {
        if (!isset($this->routes[$id])) {
            throw new \Exception('The route "' . $id . '" does not exist in module "' . $this->id . '".');
        }
        /** @var $route Route */
        $route = $this->routes[$id];
        return $route->getUrl($arguments, $absoluteURL, $parameters, $escapeArguments);
    }

    public function importClass($path, $extension = '.php')
    {
        require_once($this->definition->path . 'classes/' . $path . $extension);
    }

    public function importConfig($name, $createInstance = true)
    {
        require_once($this->definition->path . 'Configs/' . $name . '.php');

        if ($createInstance) {
            $class = NamespaceTools::getNamespaceOfInstance($this) . '\\Configs\\' . $name;
            return new $class();
        } else {
            return null;
        }
    }

    public function importConfigChain($chain)
    {
//        $namespace = NamespaceTools::getNamespaceOfInstance($this);

        foreach ($chain as $element) {
            if (class_exists($element)) {
                return new $element;
            }
//            $file = AutoLoader::translateClassName($element, $namespace, $this->path);
//            if ($element && is_file($file)) {
//                return new $element;
//            }
        }

        throw new \Exception('No configuration could be imported.');
    }

    public function importPlugin($type, $config = null, $id = null)
    {
        if ($id === null) {
            $id = str_replace('\\', '', $type);
        }

        if ($this->getPluginById($id)) {
            return null;
        }

        $definition = $this->app->getComponentDefinition($type);

        if ($config === null || is_array($config)) {
            $config = new ArrayFilter($config);
        }

        $class = $definition->class;

        /** @var $p Plugin */
        $p = new $class();
        $p->definition = $definition;
        $p->config = $config;
        $p->module = $this;
        $p->app = $this->app;
        $p->assetUrlPrefix = str_replace('\\', '/', $type) . '/';

        $p->id = $id;

        $this->pluginIds[$p->id] = $p;
        $this->plugins[] = $p;

        if (!isset($this->pluginTypes[$type])) {
            $this->pluginTypes[$type] = array($p);

        } else {
            $this->pluginTypes[$type][] = $p;
        }

        $p->init();

        return $p;
    }

    /** @return Plugin[] */
    public function getPluginsByType($type)
    {
        if (isset($this->pluginTypes[$type])) {
            return $this->pluginTypes[$type];
        }
        return null;
    }

    /** @return Plugin */
    public function getPluginById($id)
    {
        if (isset($this->pluginIds[$id])) {
            return $this->pluginIds[$id];
        }
        return null;
    }

    public function hasRoute($route)
    {
        return isset($this->routes[$route]);
    }

    /** @return Route */
    public function addErrorRoute($code, $page, $pageData = null)
    {
        $f = new ArrayFilter($pageData);
        if (!$f->has('responseCode')) {
            $f->set('responseCode', $code);
        }

        $url = null;
        $activated = false;
        $priority = 0;

        if ($code == ResponseCode::CODE_404) {
            $url = '%%url,.*%%';
            $activated = true;
            $priority = -1;
        }

        $codeDigit = substr($code, 0, 3);

        return $this->addNamedRoute('GroutError' . $codeDigit, $url, $page, $f->getData(), $priority, $activated);
    }

    public function generateContextString($uri)
    {
        return '#' . $this->id . '::' . $uri;
    }
}
