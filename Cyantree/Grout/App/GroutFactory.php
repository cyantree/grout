<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Generators\Template\TemplateGenerator;
use Cyantree\Grout\Event\Events;
use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Ui\Ui;

class GroutFactory
{
    /** @var App */
    public $app;

    /** @var \Cyantree\Grout\Event\Events */
    public $events;

    private static $_instances = array();

    /** @var ArrayFilter */
    private $_tools;

    public static function _getInstance($app, $factoryClass)
    {
        if (!isset(self::$_instances[$factoryClass.'_'.$app->id])) {
            $f = new $factoryClass();
            $f->app = $app;
            $f->_tools = new ArrayFilter(array());

            self::$_instances[$factoryClass.'_'.$app->id] = $f;
        }

        return self::$_instances[$factoryClass.'_'.$app->id];
    }

    protected function _getTaskTool($tool, $definitionClass)
    {
        $id = $tool.'_'.$this->app->currentTask->id;

        $t = $this->_tools->get($id);
        if($t){
            return $t;
        }

        if(get_class($this) != $definitionClass){
            $c = get_parent_class($this);

            $t = $c::get($this->app)->$tool();
            if($t){
                $this->_tools->set($id, $t);
                return $t;
            }
        }

        $event = $this->events->trigger($tool);

        if($event->data){
            $this->_tools->set($id, $event->data);
            return $event->data;
        }

        return null;
    }

    protected function _getAppTool($tool, $definitionClass)
    {
        $t = $this->_tools->get($tool);
        if($t){
            return $t;
        }

        if(get_class($this) != $definitionClass){
            $c = get_parent_class($this);

            $t = $c::get($this->app)->$tool();
            if($t){
                $this->_tools->set($tool, $t);
                return $t;
            }
        }

        $event = $this->events->trigger($tool);

        if($event->data){
            $this->_tools->set($tool, $event->data);
            return $event->data;
        }

        return null;
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
    public static function get($app)
    {
        return GroutFactory::_getInstance($app, __CLASS__);
    }

    /** @return TemplateGenerator */
    public function appTemplates()
    {
        if($tool = $this->_getAppTool(__FUNCTION__, __CLASS__)){
            return $tool;
        }

        $tool = new TemplateGenerator();
        $tool->app = $this->app;

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    /** @return GroutAppConfig */
    public function appConfig()
    {
        if($tool = $this->_getAppTool(__FUNCTION__, __CLASS__)){
            return $tool;
        }

        $tool = $this->app->config;

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    public function getTaskTool($tool)
    {
        $id = $this->app->currentTask->id.'_'.$tool;

        $t = $this->_tools->get($id);
        if($t){
            return $t;
        }

        if(method_exists($this, $tool)){
            return $this->{$tool}();
        }

        $event = $this->events->trigger($tool);

        if($event->data){
            $this->_tools->set($id, $event->data);
            return $event->data;
        }

        return null;
    }

    public function getAppTool($tool)
    {
        $t = $this->_tools->get($tool);
        if($t){
            return $t;
        }

        if(method_exists($this, $tool)){
            return $this->{$tool}();
        }

        $event = $this->events->trigger($tool);

        if($event->data){
            $this->_tools->set($tool, $event->data);
            return $event->data;
        }

        return null;
    }

    /** @return GroutQuick */
    public function appQuick()
    {
        if($tool = $this->_getAppTool(__FUNCTION__, __CLASS__)){
            return $tool;
        }

        $tool = new GroutQuick($this->app);
		$tool->publicAssetUrl = $this->app->publicUrl . $this->appConfig()->assetUrl;

        $this->_setAppTool(__FUNCTION__, $tool);
        return $tool;
    }

    /** @return Ui */
    public function appUi()
    {
        if($tool = $this->_getAppTool(__FUNCTION__, __CLASS__)){
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
}