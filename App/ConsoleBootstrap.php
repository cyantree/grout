<?php
namespace Cyantree\Grout\App;

class ConsoleBootstrap extends Bootstrap
{
    public function initApp()
    {
        $this->app->isConsole = true;

        parent::initApp();
    }

    public function createRequest()
    {
        global $argv;

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
}
