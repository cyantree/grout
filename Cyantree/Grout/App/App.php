<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Types\ResponseCode;
use Cyantree\Grout\AutoLoader;
use Cyantree\Grout\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\AppTools;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\NamespaceTools;
use Cyantree\Grout\Tools\ServerTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Tools\Tools;
use Cyantree\Grout\App\GroutAppConfig;

class App
{
    public $id;
    public $name = 'cyantree Grout Project';

    public $path;
    public $publicPath;
    public $url;
    public $publicUrl;
    public $dataPath;

    /** @var GroutAppConfig */
    public $config;

    public $timeConstructed;

    /** @var Module[] */
    public $modules = array();

    /** @var Module[] */
    public $moduleTypes = array();

    /** @var Module[] */
    public $moduleIds = array();

    /** @var Plugin[] */
    public $plugins = array();

    /** @var Plugin[] */
    public $pluginTypes = array();

    /** @var Plugin[] */
    public $pluginIds = array();

    /** @var Events */
    public $events;

    public $onEmergencyShutdown;

    private $_routes = array();

    private $_initiated = false;

    /** @var Task */
    public $currentTask;
    private $_otherActiveTasks = array();
    private $_taskCount = 0;

    /** @var App */
    public static $current;
    private static $_otherActiveApps = array();

    private $_emergencyShutdownInProgress;

    function __construct()
    {
        $this->events = new Events();
        $this->timeConstructed = microtime(true);

        if(self::$current){
            self::$_otherActiveApps[] = self::$current;
        }
        self::$current = $this;
    }

    /** @param $config GroutAppConfig */
    public function setConfig($config)
    {
        $config->app = $this;
        $this->config = $config;
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
        if (!$this->id) {
            $this->id = md5($this->path);
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function processRequest($request)
    {
        $request->prepare();

        $isInitiated = $this->_initiated;
        $this->_initiated = true;

        // Init modules
        // <<
        if (!$isInitiated) {
            foreach ($this->modules as $module) {
                $module->app = $this;

                if (!$module->initiated) {
                    $module->initiated = true;
                    $module->init();
                }
            }
            $this->events->trigger('log', 'Initial modules initiated');
        }
        // >>

        // Create task
        $task = new Task();
        $task->id = ++$this->_taskCount;
        $task->timeConstructed = microtime(true);
        $task->request = $request;
        $task->response = new Response();
        $task->app = $this;
        $task->url = $this->url . $task->request->url;

        if ($this->currentTask) {
            $this->_otherActiveTasks[] = $task;
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

                    if (!isset($this->_routes[$route->priority])) {
                        $this->_routes[$route->priority] = array();
                        $routePrioritiesChanged = true;
                    }
                    $this->_routes[$route->priority][] = $route;
                }
            }
        }

        // Route priorities changed, resort list
        if ($routePrioritiesChanged) {
            krsort($this->_routes);
        }
        // >>

        $this->events->trigger('log', 'Prepared module routes');

        $this->events->trigger('log0', 'Request: ' . $task->request->url);
        $this->events->trigger('log', 'Find matching routes');

        // Set back request url to compatible type
//        if ($task->request->url == '') {
//            $task->request->url = '/';
//        }

        // Get matching route
        // <<
        /** @var $foundRoute Route */
        $foundRoute = null;
        $routeVars = array();

        foreach ($this->_routes as $routePriorities) {
            foreach ($routePriorities as $route) {
                /** @var $route Route */
                if (!$route->enabled || !$route->module->routesEnabled) {
                    continue;
                }

                $res = $route->matches($task->request->url);

                if ($res['matches']) {
                    if (($route->module && $route->module->routeRetrieved($task, $route)) ||
                          ($route->plugin && $route->plugin->routeRetrieved($task, $route))
                    ) {
                        $foundRoute = $route;
                        $routeVars = $res['vars'];
                        break 2;
                    }
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
            trigger_error('[CWF] No matching addRoute found for URL "' . $task->request->url . '"', E_USER_ERROR);
        }

        $this->events->trigger('log', 'Prepare parsing');

        if(strpos($task->route->page, '@')){
            $type = explode('@', $task->route->page, 2);
            $class = $type[0];
            $action = $type[1];
        }else{
            $class = $task->route->page;
            $action = 'parseTask';
        }

        $classData = AppTools::decodeUri($class, $this, $task->route->module, $task->route->plugin);
        if ($classData[1]) {
            $class = NamespaceTools::getNamespaceOfInstance($classData[1]) . '\Pages\\' . $classData[2];
        } elseif ($classData[0]) {
            $class = NamespaceTools::getNamespaceOfInstance($classData[0]) . '\Pages\\' . $classData[2];
        } else {
            trigger_error('[CWF] No page class found for "' . $task->request->url . '" with "' . $task->route->page . '"');
            $class = null;
        }

        $task->setPage(new $class());

//        if (isset($task->page->callback)) {
//            call_user_func($task->page->callback, array($task));
//        }

        foreach ($this->modules as $module) {
            $module->beforeParsing($task);
        }

        if (!$task->page) {
            trigger_error('[CWF] No page was set', E_USER_ERROR);
        }

        $this->events->trigger('log', 'Parse request');

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();

        foreach ($this->modules as $module) {
            $module->afterParsing($task);
        }

        $this->events->trigger('log', 'Request parsed');

        // Destroy task
        foreach ($this->modules as $module) {
            $module->destroyTask($task);
        }

        if (count($this->_otherActiveTasks)) {
            $this->currentTask = array_pop($this->_otherActiveTasks);
            $this->events->trigger('currentTaskChanged', $this->currentTask);
        } else {
            $this->currentTask = null;
        }

        return $task->response;
    }

    public function destroy()
    {
        $this->events->trigger('log', '[APP] Destroy');
        foreach ($this->modules as $module) {
            $module->destroy();
        }

        if(count(self::$_otherActiveApps)){
            self::$current = array_pop(self::$_otherActiveApps);
        }else{
            self::$current = null;
        }
    }

    public function hasModule($type)
    {
        return is_file($this->path . 'modules/' . $type . '/' . $type . '.php');
    }

    public function moduleImported($type)
    {
        return isset($this->moduleTypes[$type]);
    }


    /**
     * @param $type
     * @param array|null $config
     * @return Module
     */
    public function importModule($type, $urlPrefix = null, $config = null, $priority = 0)
    {
        $pos = strrpos($type, '\\');
        if ($pos === false) {
            $class = $type;
        } else {
            $class = substr($type, strrpos($type, '\\') + 1);
        }

        if ($config === null || is_array($config)) {
            $config = new ArrayFilter($config);
        }

        $directory = str_replace('\\', '/', $type);

        $config->set('_priority', $priority);

        // Add module path to auto loading
        if (!class_exists('Grout\\' . $type, false)) {
            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $this->path . 'modules/' . $directory . '/');
            AutoLoader::registerNamespace('Grout\\' . $type . '\\', $this->path . 'modules/' . $directory . '/source/');
        }

        /** @var $m Module */
        $c = 'Grout\\' . $type . '\\' . $class;

        $m = new $c();
        $m->type = $type;
        $m->events = new Events();
        $m->app = $this;
        $m->config = & $config;
        $m->urlPrefix = $urlPrefix !== null ? $urlPrefix : '';
        $m->path = $this->path . 'modules/' . $directory . '/';
        $m->namespace = NamespaceTools::getNamespaceOfInstance($m) . '\\';

        $id = $config->get('id');
        if (!$id) {
            $id = $type;
        }
        $m->id = $id;

        $this->modules[] = $m;
        $this->moduleTypes[$type] = $m;

        $this->moduleIds[$m->id] = $m;

        if ($this->_initiated) {
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

    /** @return Module */
    public function getModuleByType($type)
    {
        if (isset($this->moduleTypes[$type])) {
            return $this->moduleTypes[$type];
        }
        return null;
    }

    /** @return Module */
    public function getModuleById($id)
    {
        if (isset($this->moduleIds[$id])) {
            return $this->moduleIds[$id];
        }
        return null;
    }

    public function getHostUrl($path = '', $absoluteURL = true, $parameters = null)
    {
        if ($parameters != null) {
            $path .= StringTools::getQueryString($parameters);
        }

        if ($absoluteURL) {
            return $this->publicUrl . $path;
        }
        return $path;
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
        if ($this->_emergencyShutdownInProgress) {
            return;
        }

        $this->_emergencyShutdownInProgress = true;

        $this->events->trigger('emergencyShutdown');

        if($this->currentTask){
            /** @var $response Response */
            $this->currentTask->page->parseError(ResponseCode::CODE_500, $reason);
        }


        $this->destroy();

        if($this->onEmergencyShutdown){
            call_user_func($this->onEmergencyShutdown, $this);
        }else{
            if($this->currentTask){
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

        if(strpos($task->route->page, '@')){
            $type = explode('@', $task->route->page, 2);
            $class = $type[0];
            $action = $type[1];
        }else{
            $class = $task->route->page;
            $action = 'parseTask';
        }

        $classData = AppTools::decodeUri($class, $this, $task->route->module, $task->route->plugin);
        if ($classData[1]) {
            $class = NamespaceTools::getNamespaceOfInstance($classData[1]) . '\Pages\\' . $classData[2];
        } elseif ($classData[0]) {
            $class = NamespaceTools::getNamespaceOfInstance($classData[0]) . '\Pages\\' . $classData[2];
        } else {
            trigger_error('[CWF] No page class found for "' . $task->request->url . '" with "' . $task->route->page . '"');
            $class = null;
        }

        $task->setPage(new $class());

        if (!$task->page) {
            trigger_error('[CWF] No page was set', E_USER_ERROR);
        }

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();
    }

    /** @param $task Task
     * @param $page Page
     */
    public function redirectTaskToPage($task, $page, $action = 'parseTask')
    {
        $task->setPage($page);

        if (!$task->page) {
            trigger_error('[CWF] No page was set', E_USER_ERROR);
        }

        $task->page->task = $task;
        $task->page->beforeParsing();
        $task->page->{$action}();
        $task->page->afterParsing();
    }
}