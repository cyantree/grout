<?php
namespace Cyantree\Grout\App\Config;

use Cyantree\Grout\App\App;

class ConfigContainer
{
    /** @var App */
    public $app;

    private $configs = array();
    private $appConfigs = array();
    private $defaultConfigs = array();

    public function __construct(App $app)
    {
        $this->app = $app;

        $this->appConfigs = array(
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

        $this->appConfigs[$priority][] = $config;
    }

    public function setDefaultConfig($id, $config, $context = null)
    {
        $this->defaultConfigs[$id] = array('config' => $config, 'context' => $context);
    }

    public function getConfig($id)
    {
        if (isset($this->configs[$id])) {
            return $this->configs[$id];
        }

        $method = 'configure' . $id;

        $config = $this->defaultConfigs[$id];

        foreach ($this->appConfigs as $priority) {
            foreach ($priority as $configFile) {
                if (method_exists($configFile, $method)) {
                    $configFile->{$method}($config['config'], $config['context']);
                }
            }
        }

        $this->configs[$id] = $config['config'];

        return $config['config'];
    }
}
