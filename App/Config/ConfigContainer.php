<?php
namespace Cyantree\Grout\App\Config;

use Cyantree\Grout\App\App;

class ConfigContainer
{
    /** @var App */
    public $app;

    private $_configs = array();
    private $_appConfigs = array();
    private $_defaultConfigs = array();

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->_appConfigs = array(
            array(), array(), array(), array(), array(), array(), array(), array(), array(), array()
        );
    }

    /**
     * @param ConfigProvider $config
     * @param int 0-9
     */
    public function addConfigProvider(ConfigProvider $config, $priority = 5)
    {
        $config->app = $this->app;

        $this->_appConfigs[$priority][] = $config;
    }

    public function setDefaultConfig($id, $config, $context = null)
    {
        $this->_defaultConfigs[$id] = array('config' => $config, 'context' => $context);
    }

    public function getConfig($id)
    {
        if (isset($this->_configs[$id])) {
            return $this->_configs[$id];
        }

        $method = 'configure' . $id;

        $config = $this->_defaultConfigs[$id];

        foreach ($this->_appConfigs as $priority) {
            foreach ($priority as $configFile) {
                if (method_exists($configFile, $method)) {
                    $configFile->{$method}($config['config'], $config['context']);
                }
            }
        }

        $this->_configs[$id] = $config['config'];

        return $config['config'];
    }
}