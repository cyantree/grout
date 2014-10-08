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

    public static function _getInstance($app, $factoryClass, $factoryContext = null, $activeModuleTypeOrInstance = null)
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

    private function _getTool($tool, $toolId, $executeFactoryMethod = false)
    {
        $t = $this->_tools->get($toolId);
        if($t){
            return $t;
        }

        if ($executeFactoryMethod && $this->_reflection->hasMethod($tool)) {
            return $this->{$tool}();
        }

        $event = $this->events->trigger($tool);
        if ($event->data) {
            $t = $event->data;

        } else {
            $declaredClass = ArrayTools::get($this->_toolClasses, $tool);

            if ($declaredClass === null) {
                if ($this->_reflection->hasMethod($tool)) {
                    $this->_toolClasses[$tool] = $declaredClass = $this->_reflection->getMethod($tool)->getDeclaringClass()->getName();

                } else {
                    $this->_toolClasses[$tool] = $declaredClass = false;
                }
            }

            if ($declaredClass && $this->_class != $declaredClass) {
                $t = $this->_getParentFactory()->$tool();
            }
        }


        if($t){
            $this->_tools->set($toolId, $t);
        }

        return $t;
    }

    protected function _getTaskTool($tool)
    {
        return $this->_getTool($tool, $tool . '_' . $this->app->currentTask->id);
    }

    protected function _deleteAppTool($tool)
    {
        $this->_tools->delete($tool);
    }

    protected function _deleteTaskTool($tool)
    {
        $this->_tools->delete($this->app->currentTask->id . '_' . $tool);
    }

    protected function _getAppTool($tool)
    {
        return $this->_getTool($tool, $tool);
    }

    protected function _setTaskTool($id, $tool)
    {
        $this->_tools->set($this->app->currentTask->id.'_'.$id, $tool);
    }

    protected function _setAppTool($id, $tool)
    {
        $this->_tools->set($id, $tool);
    }

    public function __construct()
    {
        $this->events = new Events();
    }

    public function hasTaskTool($tool)
    {
        return $this->_tools->has($this->app->currentTask->id.'_'.$tool);
    }

    public function hasAppTool($tool)
    {
        return $this->_tools->has($tool);
    }

    /** @return GroutFactory */
    public static function get(App $app = null)
    {
        return GroutFactory::_getInstance($app, __CLASS__);
    }

    /** @return TemplateGenerator */
    public function templates()
    {
        if($tool = $this->_getAppTool(__FUNCTION__)){
            return $tool;
        }

        $tool = new TemplateGenerator();
        $tool->app = $this->app;

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    public function getTaskTool($tool)
    {
        return $this->_getTool($tool, $this->app->currentTask->id . '_' . $tool, true);
    }

    public function getAppTool($tool)
    {
        return $this->_getTool($tool, $tool, true);
    }

    /** @return GroutQuick */
    public function quick()
    {
        if($tool = $this->_getAppTool(__FUNCTION__)){
            return $tool;
        }

        $tool = new GroutQuick($this->app);
        $tool->publicAssetUrl = $this->app->publicUrl . $this->app->getConfig()->assetUrl;

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    /** @return Ui */
    public function ui()
    {
        if($tool = $this->_getAppTool(__FUNCTION__)){
            return $tool;
        }

        $tool = new Ui();

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    public function log($data)
    {
        $this->app->events->trigger('log', $data);
    }

    public function logException($exception)
    {
        $this->app->events->trigger('logException', $exception);
    }
}