<?php
namespace Cyantree\Grout;

use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Tools\VarTools;

class Quick
{
    /** @var Quick */
    public static $default;
    public $defaultEscapingContext = 'html';
    public $defaultEcho = false;

    public function __construct($defaultEscapingContext = 'html', $defaultEcho = false)
    {
        $this->defaultEscapingContext = $defaultEscapingContext;
        $this->defaultEcho = $defaultEcho;
    }

    public function e($text, $context = null, $echo = null)
    {
        if ($context === null) {
            $context = $this->defaultEscapingContext;
        }
        if ($echo === null) {
            $echo = $this->defaultEcho;
        }

        if ($context == 'html') {
            $text = StringTools::escapeHtml($text);
        } else if ($context == 'js') {
            $text = StringTools::escapeJs($text);
        } else if ($context == 'url') {
            $text = urlencode($text);
        }

        if ($echo) {
            echo $text;
            return null;
        } else {
            return $text;
        }
    }

    public function k($object, $key, $defaultValue = null, $type = null, $typeArgs = null)
    {
        $data = ArrayTools::get($object, $key, $defaultValue);
        if ($type === null) {
            return $data;
        } else {
            return VarTools::prepare($data, $type, $typeArgs);
        }
    }
}