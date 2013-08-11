<?php
namespace Cyantree\Grout\App;

class GroutAppConfig
{
    public $projectTitle = 'cyantree Grout App';

    public $internalAccessKey = null;

    public $developmentMode = false;

    private $_configs = array();

    /** @var App */
    public $app;

    public function get($moduleOrPluginType, $moduleOrPluginId, $templateConfig)
    {
        if(!isset($this->_configs[$moduleOrPluginId])){
            $config = $this->_configs[$moduleOrPluginId] = $this->_create($moduleOrPluginType, $moduleOrPluginId, $templateConfig);
        }else{
            $config = $this->_configs[$moduleOrPluginId];
        }

        return $config;
    }

    protected function _create($moduleOrPluginType, $moduleOrPluginId, $templateConfig)
    {
        return $templateConfig;
    }
}