<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Generators\Template\TemplateGenerator;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Ui\Ui;

class GroutFactory
{
    /** @var App */
    public $app;

    /** @var Module */
    public $module;

    public $context;

    /** @var \Cyantree\Grout\Event\Events */
    public $events;

    private static $_instances = array();

    /** @var ArrayFilter */
    private $_tools;

    /** @var \ReflectionClass */
    private $_reflection;
    private $_toolClasses = array();

    private $_class;

    public static function getFactory($app, $factoryClass, $factoryContext = null, $activeModuleTypeOrInstance = null)
    {
        if (!$app) {
            $app = App::$current;
        }

        $module = null;

        if ($activeModuleTypeOrInstance) {
            if (is_object($activeModuleTypeOrInstance)) {
                $module = $activeModuleTypeOrInstance;
                $factoryContext = $module->id;

            } elseif ($app->currentTask && get_class($app->currentTask->module) == 'Grout\\' . $activeModuleTypeOrInstance) {
                $module = $app->currentTask->module;
                $factoryContext = $module->id;

            } else {
                $modules = $app->getModulesByType($activeModuleTypeOrInstance);
                if (count($modules) == 1) {
                    $module = $modules[0];
                    $factoryContext = $module->id;

                } else {
                    trigger_error('Can\'t find matching module ' . $activeModuleTypeOrInstance, E_USER_WARNING);

                    $factoryContext = null;
                }
            }
        }

        if (!isset(self::$_instances[$factoryClass.'_'.$factoryContext.'_'.$app->id])) {
            /** @var GroutFactory $f */
            $f = new $factoryClass();
            $f->app = $app;
            if ($module) {
                $f->module = $module;
            }
            $f->context = $factoryContext;
            $f->_tools = new ArrayFilter(array());
            $f->_reflection = new \ReflectionClass($f);
            $f->_class = get_class($f);
            $f->_onInit();

            self::$_instances[$factoryClass.'_'.$factoryContext.'_'.$app->id] = $f;
        }

        return self::$_instances[$factoryClass.'_'.$factoryContext.'_'.$app->id];
    }

    protected function _getParentFactory()
    {
        $class = get_parent_class($this);

        return $class::get($this->app);
    }

    protected function _onInit()
    {

    }

    public function getTool($name, $executeFactoryMethod = true)
    {
        $tool = $this->_tools->get($name);
        if($tool){
            return $tool;
        }

        if ($executeFactoryMethod && $this->_reflection->hasMethod($name)) {
            return $this->{$tool}();
        }

        $event = $this->events->trigger($name);
        if ($event->data) {
            $tool = $event->data;

        } else {
            $declaredClass = ArrayTools::get($this->_toolClasses, $name);

            if ($declaredClass === null) {
                if ($this->_reflection->hasMethod($name)) {
                    $this->_toolClasses[$name] = $declaredClass = $this->_reflection->getMethod($name)->getDeclaringClass()->getName();

                } else {
                    $this->_toolClasses[$name] = $declaredClass = false;
                }
            }

            if ($declaredClass && $this->_class != $declaredClass) {
                $tool = $this->_getParentFactory()->$name();
            }
        }


        if($tool){
            $this->_tools->set($name, $tool);
        }

        return $tool;
    }

    public function hasTool($name)
    {
        return $this->_tools->has($name);
    }

    public function deleteTool($name)
    {
        $this->_tools->delete($name);
    }

    public function setTool($name, $tool)
    {
        $this->_tools->set($name, $tool);
    }

    public function __construct()
    {
        $this->events = new Events();
    }
}