<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Event\Event;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;

class GroutFactory
{
    /** @var App */
    public $app;

    /** @var Module */
    public $module;

    public $context;

    /** @var \Cyantree\Grout\Event\Events */
    public $events;

    private static $instances = array();

    /** @var ArrayFilter */
    private $tools;

    /** @var \ReflectionClass */
    private $reflection;
    private $toolClasses = array();

    private $class;

    public static function get(App $app = null)
    {
        /** @var GroutFactory $factory */
        $factory = GroutFactory::getFactory($app, __CLASS__);

        return $factory;
    }

    public static function getFactory($app, $factoryClass, $factoryContext = null, $activeModuleTypeOrInstance = null)
    {
        if (!$app) {
            $app = App::$current;
        }

        /** @var Module $module */
        $module = null;

        if ($activeModuleTypeOrInstance) {
            if (is_object($activeModuleTypeOrInstance)) {
                $module = $activeModuleTypeOrInstance;
                $factoryContext = $module->id;

            } elseif ($app->currentTask
                && get_class($app->currentTask->module) == 'Grout\\' . $activeModuleTypeOrInstance
            ) {
                $module = $app->currentTask->module;
                $factoryContext = $module->id;

            } else {
                $modules = $app->getModulesByType($activeModuleTypeOrInstance);
                if (count($modules) == 1) {
                    $module = $modules[0];
                    $factoryContext = $module->id;

                } else {
                    throw new \Exception('Can\'t find matching module ' . $activeModuleTypeOrInstance);
                }
            }
        }

        $factoryId = $factoryClass . '_' . $factoryContext . '_' . $app->id;

        if (!isset(self::$instances[$factoryId])) {
            /** @var GroutFactory $f */
            $f = new $factoryClass();
            $f->app = $app;
            if ($module) {
                $f->module = $module;
            }
            $f->context = $factoryContext;
            $f->onInit();

            self::$instances[$factoryId] = $f;
        }

        return self::$instances[$factoryId];
    }

    public function __construct()
    {
        $this->events = new Events();
        $this->tools = new ArrayFilter();
        $this->reflection = new \ReflectionClass($this);
        $this->class = get_class($this);
    }

    protected function onInit()
    {

    }

    /** @return GroutFactory */
    protected function getParentFactory()
    {
        $class = get_parent_class($this);

        return $class::get($this->app);
    }

    /** @return GroutFactory */
    protected function getRootFactory()
    {
        return GroutFactory::get($this->app);
    }

    protected function retrieveTool($name, $checkParentFactories = false)
    {
        $tool = $this->tools->get($name);
        if ($tool) {
            return $tool;
        }

        $event = $this->events->trigger($name);
        if ($event->data) {
            $tool = $event->data;

        } else {
            $declaredClass = ArrayTools::get($this->toolClasses, $name);

            if ($declaredClass === null) {
                if ($this->reflection->hasMethod($name)) {
                    $this->toolClasses[$name] = $declaredClass =
                            $this->reflection->getMethod($name)->getDeclaringClass()->getName();

                } else {
                    $this->toolClasses[$name] = $declaredClass = false;
                }
            }

            if ($declaredClass && ($this->class != $declaredClass || $checkParentFactories)) {
                $parentFactory = $this->getParentFactory();

                $tool = $parentFactory ? $parentFactory->getTool($name) : null;
            }
        }

        if ($tool) {
            $this->tools->set($name, $tool);
        }

        return $tool;
    }

    public function getTool($name)
    {
        $tool = $this->tools->get($name);
        if ($tool) {
            return $tool;
        }

        if ($this->reflection->hasMethod($name)) {
            return $this->{$name}();

        } else {
            return $this->retrieveTool($name);
        }
    }

    public function hasTool($name)
    {
        return $this->tools->has($name);
    }

    public function deleteTool($name)
    {
        $this->tools->delete($name);
    }

    public function setTool($name, $tool)
    {
        $this->tools->set($name, $tool);

        $this->events->trigger($name . '.changed', $tool);
    }

    public function linkTools($tools, GroutFactory $providerFactory)
    {
        if (!is_array($tools)) {
            $tools = array($tools);
        }

        foreach ($tools as $tool) {
            $this->events->join($tool, function(Event $e, GroutFactory $providerFactory)
            {
                if (!$e->data) {
                    $e->data = $providerFactory->getTool($e->type);
                }
            }, $providerFactory);
        }
    }
}
