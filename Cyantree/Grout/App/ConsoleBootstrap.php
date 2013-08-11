<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Filter\ArrayFilter;

class ConsoleBootstrap
{
    /** @var App */
    public $app;

    protected $_frameworkPath;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function init($frameworkPath)
    {
        global $argv;

        $this->_frameworkPath = $frameworkPath;

        $this->_setBasePaths();

        $r = new Request();

        if(count($argv) > 1){
            $r->url = $argv[1];
        }

        $get = array();
        if(count($argv) > 2){
            $args = array_splice($args, 2);
            foreach($args as $arg){
                if(substr($arg, 0, 2) == '--'){
                    $get[substr($arg, 2)] = true;
                }elseif(substr($arg, 0, 1) == '-'){
                    $s = explode('=', $arg, 2);

                    $get[substr($s[0], 1)] = $s[1];
                }else{
                    $get[] = $arg;
                }
            }
        }
        $r->get->setData($get);

        return $r;
    }

    protected function _setBasePaths()
    {
        // Set server base paths
        $this->app->path = str_replace('\\', '/', realpath($this->_frameworkPath)).'/';
        $this->app->publicPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])).'/';
    }
}