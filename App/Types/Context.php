<?php
namespace Cyantree\Grout\App\Types;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\ComponentDefinition;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;

class Context
{
    /** @var App */
    public $app;

    /** @var Module */
    public $module;

    /** @var ComponentDefinition */
    public $moduleDefinition;

    /** @var Plugin */
    public $plugin;

    /** @var ComponentDefinition */
    public $pluginDefinition;

    /** @var string */
    public $uri;

    public function __construct($uri = null, App $app = null, Module $module = null, Plugin $plugin = null)
    {
        $this->uri = $uri;
        $this->app = $app;
        $this->module = $module;
        $this->plugin = $plugin;
    }
}
