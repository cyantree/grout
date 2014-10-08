<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;

class AppTools
{
    /**
     * @param $uri
     * @param $app App
     * @param $module Module
     * @param $plugin Plugin
     * @return array
     */
    public static function decodeUri($uri, $app, $module = null, $plugin = null)
    {
        $uri = explode(':', $uri, 3);
        $c = count($uri);

        if ($c == 1) {
            return array($module, $plugin, $uri[0]);

        } else if ($c == 2) {
            if ($uri[0] !== '') {
                $module = $app->getModuleById($uri[0]);

            } elseif($uri[0] === 'App') {
                $module = null;
            }

            return array($module, null, $uri[1]);

        } else if ($c == 3) {
            $moduleId = $uri[0];
            $pluginId = $uri[1];

            if ($moduleId === 'App') {
                $module = null;
                $plugin = null;

            } elseif ($moduleId === 'Module' || $moduleId === '') {
                if ($pluginId !== '') {
                    $plugin = $module->pluginIds[$pluginId];

                } else {
                    $plugin = null;
                }

            } elseif ($moduleId !== '') {
                $module = $app->getModuleById($moduleId);

                if ($pluginId !== '') {
                    $plugin = $module->pluginIds[$pluginId];

                } else {
                    $plugin = null;
                }

            }

            return array($module, $plugin, $uri[2]);
        }

        return null;
    }

    public static function createConfigChain($primaryConfig, $defaultConfig, $namespace = '', $useServerAdmin = true, $useServerName = true, $prefix = '')
    {
        $chain = array();
        if ($primaryConfig) {
            $chain[] = $namespace . '\\' . $prefix . $primaryConfig . 'Config';
        }
        if ($useServerAdmin) {
            $chain[] = $namespace . '\\' . $prefix . StringTools::camelCase(StringTools::toURLPart(ArrayTools::get($_SERVER, 'SERVER_ADMIN')), '-') . 'Config';
        }
        if ($useServerName) {
            $chain[] = $namespace . '\\' . $prefix . StringTools::camelCase(StringTools::toURLPart(ArrayTools::get($_SERVER, 'HTTP_HOST')), '-') . 'Config';
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

                if($escapeArguments){
                    $res .= urlencode($arguments[$type[0]]);
                }else{
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
            if($string != '' && substr($string, -1) !== '/'){
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