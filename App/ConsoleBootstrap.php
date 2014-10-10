<?php
namespace Cyantree\Grout\App;

class ConsoleBootstrap
{
    /** @var App */
    public $app;

    public $applicationPath;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function init()
    {
        global $argv;

        $this->app->isConsole = true;

        $this->setBasePaths();

        $r = new Request();

        if (count($argv) > 1) {
            $r->url = $argv[1];

            if (substr($r->url, -1, 1) != '/') {
                $r->url .= '/';
            }
        }

        $get = array();
        if (count($argv) > 2) {
            $args = array_splice($argv, 2);
            foreach ($args as $arg) {
                if (substr($arg, 0, 2) == '--') {
                    $get[substr($arg, 2)] = true;

                } elseif (substr($arg, 0, 1) == '-') {
                    $s = explode('=', $arg, 2);

                    $get[substr($s[0], 1)] = $s[1];

                } else {
                    $get[] = $arg;
                }
            }
        }
        $r->get->setData($get);

        return $r;
    }

    protected function setBasePaths()
    {
        // Set server base paths
        $this->app->path = str_replace('\\', '/', realpath($this->applicationPath)) . '/';
        $this->app->publicPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'])) . '/';
    }
}
