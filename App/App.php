<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Config\ConfigContainer;
use Cyantree\Grout\App\Types\Context;
use Cyantree\Grout\App\Types\ResponseCode;
use Cyantree\Grout\AutoLoader;
use Cyantree\Grout\DataStorage;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;
use Cyantree\Grout\Tools\NamespaceTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Tools\Tools;

class App
{
    public $id;

    public $path;
    public $publicPath;
    public $url;
    public $publicUrl;
    public $publicAssetPath;
    public $publicAssetUrl;
    public $publicAssetUrlIsAbsolute = false;
    public $dataPath;

    public $isConsole = false;

    /** @var ConfigContainer */
    public $configs;

    public $timeConstructed;

    /** @var Module[] */
    public $modules = array();

    /** @var Module[] */
    public $moduleTypes = array();

    /** @var Module[] */
    public $moduleIds = array();

    /** @var Events */
    public $events;

    public $onEmergencyShutdown;

    /** @var DataStorage */
    public $dataStorage;

    /** @var DataStorage */
    public $cacheStorage;

    private $routes = array();

    private $initiated = false;

    /** @var Task */
    public $currentTask;
    private $otherActiveTasks = array();
    private $taskCount = 0;

    /** @var App */
    public static $current;
    private static $otherActiveApps = array();

    private $emergencyShutdownInProgress;

    public function __construct($id = null, $timeConstructed = null)
    {
        $this->id = $id ? $id : mt_rand(0, 9999);

        $this->events = new Events();
        $this->timeConstructed = $timeConstructed ? $timeConstructed : microtime(true);
        $this->configs = new ConfigContainer($this);
        $this->configs->setDefaultConfig('GroutApp', new GroutAppConfig());

        if (self::$current) {
            self::$otherActiveApps[] = self::$current;
        }

        self::$current = $this;
    }

    /** @return GroutAppConfig */
    public function getConfig()
    {
        /** @var GroutAppConfig $config */
        $config = $this->configs->getConfig('GroutApp');
        return $config;
    }

    public static function initEnvironment()
    {
        mb_internal_encoding('UTF-8');
        date_default_timezone_set('UTC');
        Tools::init();
    }

    public function parseUri($uri)
    {
        if ($uri === null || $uri === '') {
            return $uri;
        }

        $uri = explode('://', $uri, 2);

        if ($uri[0] === 'data') {
            $uri = $this->dataPath . $uri[1];

        } elseif ($uri[0] === 'path') {
            $uri = $this->path . $uri[1];

        } elseif ($uri[0] === 'publicPath') {
            $uri = $this->publicPath . $uri[1];

        } elseif ($uri[0] === 'url') {
            $uri = $this->url . $uri[1];

        } elseif ($uri[0] === 'publicUrl') {
            $uri = $this->publicUrl . $uri[1];

        } else {
            $uri = implode('://', $uri);
        }

        return $uri;
    }

    public function init()
    {
        $this->dataStorage = new DataStorage($this->dataPath . 'data/');
        $this->cacheStorage = new DataStorage($this->dataPath . 'cache/');

        $this->initiated = true;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function processRequest($request)
    {
        $request->prepare();

        // Create task
        $task = new Task();
        $task->id = ++$this->taskCount;
        $task->timeConstructed = microtime(true);
        $task->request = $request;
        $task->response = new Response();
        $task->app = $this;
        $task->url = $this->url . $task->request->url;

        if ($this->currentTask) {
            $this->otherActiveTasks[] = $task;
        }

        $this->currentTask = $task;
        $this->events->trigger('currentTaskChanged', $task);

        // Init task
        foreach ($this->modules as $module) {
            $module->initTask($task);
        }

        $this->events->trigger('log', 'Initiated task');


        // Init module routes
        // <<
        $routePrioritiesChanged = false;

        foreach ($this->modules as $module) {
            if ($module->routesChanged) {
                $modulePriority = $module->config->get('_priority', 0);

                $keys = array_keys($module->routes);
                foreach ($keys as $key) {
                    $route = $module->routes[$key];
                    if ($route->registeredInApp) {
                        continue;
                    }

                    $route->priority += $modulePriority;

                    $route->registeredInApp = true;
//                    $route->init();

                    if (!isset($this->routes[$route->priority])) {
                        $this->routes[$route->priority] = array();
                        $routePrioritiesChanged = true;
                    }
                    $this->routes[$route->priority][] = $route;
                }
            }
        }

        // Route priorities changed, resort list
        if ($routePrioritiesChanged) {
            krsort($this->routes);
        }
        // >>

        $this->events->trigger('log', 'Prepared module routes');

        $this->events->trigger('log0', 'Request: ' . $task->request->url);
        $this->events->trigger('log', 'Find matching routes');

        // Get matching route
        // <<
        /** @var $foundRoute Route */
        $foundRoute = null;
        $routeVars = array();

        foreach ($this->routes as $routePriorities) {
            foreach ($routePriorities as $route) {
                /** @var $route Route */
                if (!$route->enabled || !$route->module->routesEnabled) {
                    continue;
                }

                $res = $route->matches($task->request->url, $task->request->method);

                if (!$res['matches']) {
                    continue;
                }

                if ($route->data->has('onMatch')) {
                    /** @var $onMatch callable */
                    $onMatch = $route->data->get('onMatch');

                    if (!$onMatch($task->request->url, $res['vars'])) {
                        continue;
                    }
                }

                if (($route->module && $route->module->routeRetrieved($task, $route))
                    || ($route->plugin && $route->plugin->routeRetrieved($task, $route))
                ) {
                    $foundRoute = $route;
                    $routeVars = $res['vars'];
                    break 2;
                }
            }
        }
        // >>

        // Set back request url to compatible type
//        if ($task->request->url == '/') {
//            $task->request->url = './';
//        }

        // Prepare route
        if ($foundRoute) {
            $task->setRoute($foundRoute, $routeVars);

        } else {
            trigger_error('No matching route found for URL "' . $task->request->url . '"', E_USER_ERROR);
        }

        $this->events->trigger('log', 'Prepare parsing');

        if (strpos($task->route->page, '@')) {
            $type = explode('@', $task->route->page, 2);
            $class = $type[0];
            $action = $type[1];

        } else {
            $class = $task->route->page;
            $action = 'parseTask';
        }

        $context = AppTools::decodeContext($class, $this, $task->route->module, $task->route->plugin);
        if ($context->plugin) {
            $class = $context->plugin->namespace . $context->uri;

        } elseif ($context->module) {
            $class = $context->module->namespace . $context->uri;

        } else {
            throw new \Exception('No page class found for "' . $task->request->url . '" with "' . $task->route->page . '"');
        }

        $task->setPage(new $class());

//        if (isset($task->page->callback)) {
//            call_user_func($task->page->callback, array($task));
//        }

        $this->events->trigger('beforeParsing', $task);

        foreach ($this->modules as $module) {
            $module->beforeParsing($task);
        }

        if (!$task->page) {
            trigger_error('No page was set', E_USER_ERROR);
        }

        $this->events->trigger('log', 'Parse request');

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();

        $this->events->trigger('afterParsing', $task);

        foreach ($this->modules as $module) {
            $module->afterParsing($task);
        }

        $this->events->trigger('log', 'Request parsed');

        // Destroy task
        foreach ($this->modules as $module) {
            $module->destroyTask($task);
        }

        if (count($this->otherActiveTasks)) {
            $this->currentTask = array_pop($this->otherActiveTasks);
            $this->events->trigger('currentTaskChanged', $this->currentTask);

        } else {
            $this->currentTask = null;
        }

        return $task->response;
    }

    public function destroy()
    {
        $this->events->trigger('log', 'Destroy');

        $this->events->trigger('destroy');

        foreach ($this->modules as $module) {
            $module->destroy();
        }

        if (count(self::$otherActiveApps)) {
            self::$current = array_pop(self::$otherActiveApps);

        } else {
            self::$current = null;
        }
    }

    public function hasModule($type)
    {
        return is_file($this->path . 'modules/' . $type . '/' . $type . '.php');
    }

    public function importModuleNamespace($type)
    {
        if (!class_exists('Grout\\' . $type)) {
            $directory = $this->path . 'modules/' . str_replace('\\', '/', $type) . '/';

            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $directory);
            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $directory . 'source/');
        }
    }

    public function importPluginNamespace($type)
    {
        // Add plugin path to auto loading
        if (!class_exists('Grout\\' . $type)) {
            $directory = $this->path . 'plugins/' . str_replace('\\', '/', $type) . '/';

            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $directory);
            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $directory . 'source/');
        }
    }


    /**
     * @param $type
     * @param string $urlPrefix
     * @param array $config
     * @param string $id
     * @param int $priority
     * @return Module|null
     */
    public function importModule($type, $urlPrefix = null, $config = null, $id = null, $priority = 0)
    {
        if ($id === null) {
            $id = str_replace('\\', '', $type);

            if ($this->getModuleById($id)) {
                $id .= '_' . count($this->modules);
            }
        }

        if ($this->getModuleById($id)) {
            return null;
        }

        $pos = strrpos($type, '\\');
        if ($pos === false) {
            $class = $type;

        } else {
            $class = substr($type, strrpos($type, '\\') + 1);
        }

//        $directory = str_replace('\\', '/', $type);
//        $path = $this->path . 'modules/' . $directory . '/';

        if ($config === null || is_array($config)) {
            $config = new ArrayFilter($config);
        }

        $config->set('_priority', $priority);

        // Add module path to auto loading
        $this->importModuleNamespace($type);

        /** @var $m Module */
        $c = 'Grout\\' . $type . '\\' . $class;

        $reflection = new \ReflectionClass($c);
        $path = dirname($reflection->getFileName()) . '/';

        $m = new $c();
        $m->type = $type;
        $m->events = new Events();
        $m->app = $this;
        $m->config = & $config;
        $m->urlPrefix = $urlPrefix !== null ? $urlPrefix : '';
        $m->assetUrlPrefix = str_replace('\\', '/', $type) . '/';
        $m->path = $path;
        $m->namespace = NamespaceTools::getNamespaceOfInstance($m) . '\\';

        $m->id = $id;

        $this->modules[] = $m;
        if (!isset($this->moduleTypes[$type])) {
            $this->moduleTypes[$type] = array($m);

        } else {
            $this->moduleTypes[$type][] = $m;
        }

        $this->moduleIds[$m->id] = $m;

        if ($this->initiated) {
            $m->init();

            /* TODO: War aus nicht mehr bekanntem Grund auskommentiert, weil irgendetwas doppelt aufgerufen wurde */
            if ($this->currentTask) {
                $m->initTask($this->currentTask);
            }
        }

        return $m;
    }

    public function importClass($class, $extension = '.php')
    {
        require_once($this->path . 'classes/' . $class . $extension);
    }

    /** @return Module[] */
    public function getModulesByType($type)
    {
        if (isset($this->moduleTypes[$type])) {
            return $this->moduleTypes[$type];
        }

        return array();
    }

    /** @return Module */
    public function getModuleById($id, $throwExceptionIfNotExists = false)
    {
        if (isset($this->moduleIds[$id])) {
            return $this->moduleIds[$id];
        }

        if ($throwExceptionIfNotExists) {
            throw new \Exception('Module ' . $id . ' does not exist.');
        }

        return null;
    }

    public function getPublicUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        if ($parameters != null) {
            $path .= StringTools::getQueryString($parameters);
        }

        if ($absoluteURL) {
            return $this->publicUrl . $path;
        }

        return $path;
    }

    public function getPublicAssetUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        if ($parameters != null) {
            $path .= StringTools::getQueryString($parameters);
        }

        if ($absoluteURL && !$this->publicAssetUrlIsAbsolute) {
            return $this->publicUrl . $this->publicAssetUrl . $path;
        }

        return $this->publicAssetUrl . $path;
    }

    public function getUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        if ($parameters != null) {
            $path .= StringTools::getQueryString($parameters);
        }

        if ($absoluteURL) {
            return $this->url . $path;
        }

        return $path;
    }

    public function emergencyShutdown($reason)
    {
        if ($this->emergencyShutdownInProgress) {
            return;
        }

        $this->emergencyShutdownInProgress = true;

        $this->events->trigger('emergencyShutdown');

        if ($this->currentTask) {
            /** @var $response Response */
            $this->currentTask->page->parseError(ResponseCode::CODE_500, $reason);
        }


        $this->destroy();

        if ($this->onEmergencyShutdown) {
            call_user_func($this->onEmergencyShutdown, $this);

        } else {
            if ($this->currentTask) {
                $this->currentTask->response->postHeaders();
                echo $this->currentTask->response->content;

            }
            exit;
        }
    }

    /** @param $task Task
     */
    public function redirectTaskToUrl($task, $url)
    {
        $task->response->code = ResponseCode::CODE_302;
        $task->response->headers['Location'] = $url;
        $task->response->content = '';
    }

    /** @param $task Task
     * @param $route Route
     */
    public function redirectTaskToRoute($task, $route)
    {
        $task->setRoute($route);

        if (strpos($task->route->page, '@')) {
            $type = explode('@', $task->route->page, 2);
            $class = $type[0];
            $action = $type[1];

        } else {
            $class = $task->route->page;
            $action = 'parseTask';
        }

        $context = AppTools::decodeContext($class, $this, $task->route->module, $task->route->plugin);
        if ($context->plugin) {
            $class = $context->plugin->namespace . $context->uri;

        } elseif ($context->module) {
            $class = $context->module->namespace . $context->uri;

        } else {
            throw new \Exception('No page class found for "' . $task->request->url . '" with "' . $task->route->page . '"');
        }

        $task->setPage(new $class());

        if (!$task->page) {
            throw new \Exception('No page was set');
        }

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();
    }

    /** @param $task Task
     * @param $page Page
     */
    public function redirectTaskToPage($task, $page, $action = 'parseTask', Context $context = null)
    {
        if ($context) {
            $task->setContext($context);
        }

        $task->setPage($page);

        if (!$task->page) {
            throw new \Exception('No page was set');
        }

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();
    }
}
