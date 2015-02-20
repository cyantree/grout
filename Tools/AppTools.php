<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;
use Cyantree\Grout\App\Types\Context;

class AppTools
{
    public static function createConfigChain(
        $primaryConfig,
        $defaultConfig,
        $namespace = '',
        $useServerAdmin = true,
        $useServerName = true,
        $prefix = ''
    ) {
        $chain = array();
        if ($primaryConfig) {
            $chain[] = $namespace . '\\' . $prefix . $primaryConfig . 'Config';
        }
        if ($useServerAdmin) {
            $chain[] = $namespace . '\\' . $prefix
                . StringTools::camelCase(StringTools::toURLPart(ArrayTools::get($_SERVER, 'SERVER_ADMIN')), '-')
                . 'Config';
        }
        if ($useServerName) {
            $chain[] = $namespace . '\\' . $prefix
                . StringTools::camelCase(StringTools::toURLPart(ArrayTools::get($_SERVER, 'HTTP_HOST')), '-')
                . 'Config';
        }
        if ($defaultConfig) {
            $chain[] = $namespace . '\\' . $prefix . $defaultConfig . 'Config';
        }

        return $chain;
    }

    public static function encodePageUrlString($string, $arguments, $escapeArguments = true)
    {
        $string = explode('%%', $string);
        if (count($string) == 1) {
            return $string[0];
        }

        $res = '';
        $isInside = false;

        foreach ($string as $item) {
            if (!$isInside) {
                $res .= $item;
            } else {
                $type = explode(',', $item, 2);

                if ($escapeArguments) {
                    $res .= urlencode($arguments[$type[0]]);
                } else {
                    $res .= $arguments[$type[0]];
                }
            }

            $isInside = !$isInside;
        }

        return $res;
    }

    public static function decodePageUrlString($string)
    {
        $res = array('eReg' => null, 'expression' => null, 'mappings' => null);

        $string = explode('%%', $string);

        if (count($string) == 1) {
            $string = $string[0];
            if ($string != '' && substr($string, -1) !== '/') {
                $string .= '/';
            }

            $res['expression'] = $string;
            $res['eReg'] = false;
            return $res;
        }

        $eReg = '';
        $mappings = array();
        $isInside = false;

        foreach ($string as $item) {
            if (!$isInside) {
                $eReg .= preg_quote($item, '@');

            } else {
                $type = explode(',', $item, 2);
                $mappings[] = $type[0];

                if (count($type) == 2) {
                    $type = '(' . str_replace('@', '\@', $type[1]) . ')';
                } else {
                    $type = '([^/]*)';
                }

                $eReg .= $type;
            }

            $isInside = !$isInside;
        }

        $res['eReg'] = true;
        $res['expression'] = '@^' . $eReg . '/?$@';
        $res['mappings'] = & $mappings;

        return $res;
    }
}
