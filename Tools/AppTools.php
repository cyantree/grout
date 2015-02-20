<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\App\App;
use Cyantree\Grout\App\Module;
use Cyantree\Grout\App\Plugin;
use Cyantree\Grout\App\Types\Context;

class AppTools
{
    /**
     * @param $contextString
     * @param $app App
     * @param $module Module
     * @param $plugin Plugin
     * @return Context
     */
    public static function decodeContext($contextString, App $app, $module = null, $plugin = null)
    {
        // TODO: Ergebnis ohne Einbeziehung von module und plugin könnte gecachet werden.
        // TODO: Syntax so gut? #... für ID, .[...] für Typ

        $contextPieces = explode(':', $contextString, 3);
        $c = count($contextPieces);

        $context = new Context();
        $context->app = $app;

        if ($c == 1) {
            $context->uri = $contextString;
            $context->module = $module;
            $context->moduleDefinition = $module ? $module->definition : null;
            $context->plugin = $plugin;
            $context->pluginDefinition = $plugin ? $plugin->definition : null;

        } elseif ($c == 2) {
            throw new \Exception('Invalid context ' . $contextString);

        } else {
            $context->uri = $contextPieces[2];

            $moduleString = $contextPieces[0];
            $pluginString = $contextPieces[1];

            if ($moduleString == '') {

            } elseif ($moduleString == 'Module') {
                $context->module = $module;
                $context->moduleDefinition = $context->module->definition;

            } elseif ($moduleString[0] === '#') {
                $context->module = $app->getModuleById(substr($moduleString, 1));
                $context->moduleDefinition = $context->module->definition;

            } elseif ($moduleString[0] === '.') {
                $context->moduleDefinition = $app->getComponentDefinition(substr($contextPieces[0], 1));

            } else {
                throw new \Exception('Invalid context ' . $contextString);
            }

            if ($pluginString == '') {

            } elseif ($pluginString[0] === '#') {
                if (!$context->module) {
                    throw new \Exception('Invalid context ' . $contextString);
                }

                $context->plugin = $context->module->pluginIds[substr($pluginString, 1)];
                $context->pluginDefinition = $context->plugin->definition;

            } elseif ($pluginString[0] === '.') {
                $context->pluginDefinition = $app->getComponentDefinition(substr($pluginString, 1));

            } else {
                throw new \Exception('Invalid context ' . $contextString);
            }
        }

        return $context;
    }

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
