<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class ConfigChain
{
    public $namespace;

    public $prefix;

    private $_classes = array();

    public function __construct($namespace = null, $prefix = null)
    {
        $this->namespace = $namespace;
        $this->prefix = $prefix;
    }

    public function checkConfig($name)
    {
        if (!$name) {
            return;
        }

        $this->_classes[] = $this->namespace . $this->prefix . $name . 'Config';
    }

    public function checkMachineName()
    {
        $this->checkConfig(StringTools::camelCase(StringTools::toUrlPart(php_uname('n')), '-'));
    }

    public function checkHttpHost($serverArgs)
    {
        $val = ArrayTools::get($serverArgs, 'HTTP_HOST');
        if ($val !== null) {
            $this->checkConfig(StringTools::camelCase(StringTools::toUrlPart($val), '-'));
        }
    }

    public function checkServerAdmin($serverArgs)
    {
        $val = ArrayTools::get($serverArgs, 'SERVER_ADMIN');
        if ($val !== null) {
            $this->checkConfig(StringTools::camelCase(StringTools::toUrlPart($val), '-'));
        }
    }

    public function getChain()
    {
        return $this->_classes;
    }
}