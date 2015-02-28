<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Config\ConfigContainer;
use Cyantree\Grout\App\Types\Context;
use Cyantree\Grout\App\Types\ResponseCode;
use Cyantree\Grout\DataStorage;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
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

    /** @var ComponentDefinition[] */
    private $componentDefinitions = array();

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
     * @return Response
     */
    public function processRequest(Request $request)
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

        if ($this->isConsole) {
            $task->response->ignoreHeaders();
        }

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
                foreach ($module->routes as $name => $route) {
                    if ($route->registeredInApp) {
                        continue;
                    }

                    if (!isset($this->routes[$route->priority])) {
                        $this->routes[$route->priority] = array($route);
                        $routePrioritiesChanged = true;

                    } else {
                        $this->routes[$route->priority][] = $route;
                    }

                    $route->registeredInApp = true;
                }

                $module->routesChanged = false;
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

                $event = $route->events->trigger('retrieved', null, array('task' => $task, 'route' => $route));

                if ($event->data) {
                    $foundRoute = $event->data;
                    $routeVars = array();
                    break 2;
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

        $context = $this->decodeContext($class, $task->route->module, $task->route->plugin);
        if ($context->pluginDefinition) {
            $class = $context->pluginDefinition->namespace . $context->uri;

        } elseif ($context->moduleDefinition) {
            $class = $context->moduleDefinition->namespace . $context->uri;

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

    public function getComponentDefinitions()
    {
        return $this->componentDefinitions;
    }

    public function getComponentDefinition($type)
    {
        if (isset($this->componentDefinitions[$type])) {
            return $this->componentDefinitions[$type];
        }

        $definition = new ComponentDefinition();
        $definition->type = $type;

        $pos = strrpos($type, '\\');
        if ($pos === false) {
            $class = $type;

        } else {
            $class = substr($type, strrpos($type, '\\') + 1);
        }

        $definition->namespace = 'Grout\\' . $type . '\\';
        $class = $definition->namespace . $class;

        $definition->class = $class;

        $reflection = new \ReflectionClass($class);
        $definition->path = dirname($reflection->getFileName()) . '/';

        $this->componentDefinitions[$type] = $definition;

        if (method_exists($class, 'setup')) {
            $class::setup($this);
        }

        return $definition;
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

        $definition = $this->getComponentDefinition($type);

        if ($config === null || is_array($config)) {
            $config = new ArrayFilter($config);
        }

        $class = $definition->class;

        /** @var $m Module */
        $m = new $class();
        $m->priority = $priority;
        $m->definition = $definition;
        $m->events = new Events();
        $m->app = $this;
        $m->config = & $config;
        $m->urlPrefix = $urlPrefix !== null ? $urlPrefix : '';
        $m->assetUrlPrefix = str_replace('\\', '/', $type) . '/';

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

            if ($this->currentTask) {
                $m->initTask($this->currentTask);
            }
        }

        return $m;
    }

    public function setupComponent($type)
    {
        $this->getComponentDefinition($type);
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

    public function getPublicAssetPath($path = '')
    {
        return $this->publicAssetPath . $path;
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

        } else {
            // TODO: Happens when parsing error occurred. Should be possible to show error page
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

    public function redirectTaskToUrl(Task $task, $url)
    {
        $task->response->code = ResponseCode::CODE_302;
        $task->response->headers['Location'] = $url;
        $task->response->content = '';
    }

    public function redirectTaskToRoute(Task $task, Route $route)
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

        $context = $this->decodeContext($class, $task->route->module, $task->route->plugin);
        if ($context->plugin) {
            $class = $context->plugin->definition->namespace . $context->uri;

        } elseif ($context->module) {
            $class = $context->module->definition->namespace . $context->uri;

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

    public function redirectTaskToPage(Task $task, Page $page, $action = 'parseTask', Context $context = null)
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

    /**
     * @return Context
     */
    public function decodeContext($contextString, Module $module = null, Plugin $plugin = null)
    {
        // TODO: Ergebnis ohne Einbeziehung von module und plugin könnte gecachet werden.
        // TODO: Syntax so gut? #... für ID, .[...] für Typ

        $contextPieces = explode(':', $contextString, 3);
        $c = count($contextPieces);

        $context = new Context();
        $context->app = $this;

        if ($c == 1) {
            $context->uri = $contextString;
            $context->module = $module;
            $context->moduleDefinition = $module ? $module->definition : null;
            $context->plugin = $plugin;
            $context->pluginDefinition = $plugin ? $plugin->definition : null;

        } elseif ($c == 2) {
            throw new \Exception('Invalid context ' . $contextString);

        } else {
            $context->uri = $contextPieces[2];

            $moduleString = $contextPieces[0];
            $pluginString = $contextPieces[1];

            if ($moduleString == '') {

            } elseif ($moduleString == '#') {
                $context->module = $module;
                $context->moduleDefinition = $context->module->definition;

            } elseif ($moduleString == '.') {
                $context->moduleDefinition = $context->module->definition;

            } elseif ($moduleString[0] === '#') {
                $context->module = $this->getModuleById(substr($moduleString, 1));
                $context->moduleDefinition = $context->module->definition;

            } elseif ($moduleString[0] === '.') {
                $context->moduleDefinition = $this->getComponentDefinition(substr($contextPieces[0], 1));

            } else {
                throw new \Exception('Invalid context ' . $contextString);
            }

            if ($pluginString == '') {

            } elseif ($pluginString == '#') {
                $context->plugin = $plugin;
                $context->pluginDefinition = $context->plugin->definition;

            } elseif ($pluginString == '.') {
                $context->pluginDefinition = $plugin->definition;

            } elseif ($pluginString[0] === '#') {
                if (!$context->module) {
                    throw new \Exception('Invalid context ' . $contextString);
                }

                $context->plugin = $context->module->pluginIds[substr($pluginString, 1)];
                $context->pluginDefinition = $context->plugin->definition;

            } elseif ($pluginString[0] === '.') {
                $context->pluginDefinition = $this->getComponentDefinition(substr($pluginString, 1));

            } else {
                throw new \Exception('Invalid context ' . $contextString);
            }
        }

        return $context;
    }
}
