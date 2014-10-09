<?php
namespace Cyantree\Grout;


/**
 * Provides namespace based auto loading. Also supports custom class mapping.
 * @package Cyantree\Grout
 */
class AutoLoader
{
    /**
     * @var array Associative array in form of className => classFilePath.
     */
    public static $customMappings = array();

    /**
     * @var array Stores namespaces
     */
    private static $namespaces;

    /**
     * Initializes the auto loader and registers the Grout namespace.
     */
    public static function init($prepend = false)
    {
        spl_autoload_register(array('\\Cyantree\\Grout\\AutoLoader', 'onAutoLoad'), true, $prepend);
//        self::registerNamespace('Cyantree\\Grout\\', __DIR__ . '/');
    }

    /** Registers a namespace for auto loading.
     * @param string $namespace Namespace like \Vendor\Product\. Must end with a trailing \.
     * @param string $directory Directory which contains all files. Must end with a trailing /.
     * @param string $extension Extension of files.
     * @param bool $prioritize Namespace definition has priority over previous definitions
     */
    public static function registerNamespace($namespace, $directory, $extension = '.php', $prioritize = false)
    {
        if (self::$namespaces === null) {
            self::$namespaces = array();
        }

        $directory = str_replace('\\', '/', realpath($directory)) . '/';

        if ($prioritize) {
            array_unshift(self::$namespaces, array($namespace, $directory, strlen($namespace), $extension));

        } else {
            self::$namespaces[] = array($namespace, $directory, strlen($namespace), $extension);
        }
    }

    public static function translateClassName($className, $namespace, $directory, $extension = '.php')
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        $directory = rtrim($directory, '/') . '/';

        return $directory . str_replace('\\', '/', substr($className, strlen($namespace))) . $extension;
    }

    /** Will be called by PHP to invoke the auto loader.
     * @param string $class Class to be loaded.
     * @return bool Success of auto loading.
     */
    public static function onAutoLoad($class)
    {
        if (isset(self::$customMappings[$class])) {
            require_once(self::$customMappings[$class]);

            return true;
        }

        if (self::$namespaces !== null) {
            foreach (self::$namespaces as $data) {
                $ns = $data[0];

                $nsl = $data[2];


                if (substr($class, 0, $nsl) !== $ns) {
                    continue;
                }

                $dir = $data[1];
                $ext = $data[3];

                $file = $dir . str_replace('\\', '/', substr($class, $nsl)) . $ext;

                if (is_file($file)) {
                    require_once($file);

                    return true;
                }
            }
        }

        return false;
    }
}
