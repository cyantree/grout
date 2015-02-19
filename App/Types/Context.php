<?php
namespace Cyantree\Grout\App\Types;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;

class Context
{
    /** @var App */
    public $app;

    /** @var Module */
    public $module;

    /** @var Plugin */
    public $plugin;

    /** @var string */
    public $uri;

    public $data;

    public function __construct($uri, App $app, Module $module = null, Plugin $plugin = null, $data = null)
    {
        $this->uri = $uri;
        $this->app = $app;
        $this->module = $module;
        $this->plugin = $plugin;
        $this->data = $data;
    }
}
