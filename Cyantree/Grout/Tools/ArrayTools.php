<?php
namespace Cyantree\Grout\Tools;

class ArrayTools
{
    public static function sortAssociativeArray($array, $order){
        $r = array();
        foreach($order as $o){
            $r[$o] = $array[$o];
        }

        return $r;
    }
    public static function get($array, $key, $defaultValue = null)
    {
        if (!is_array($array)) {
            return $defaultValue;
        }
        if (array_key_exists($key, $array)) return $array[$key];

        return $defaultValue;
    }

    public static function getPrepared($array, $key, $type = 'string', $args = null, $defaultValue = null)
    {
        return VarTools::prepare(self::get($array, $key, $defaultValue), $type, $args);
    }

    public static function prepare($array, $type = 'string', $args = null)
    {
        // file, int, float, bool, string, line, raw

        if ($array === null || !is_array($array)) return array();

        if ($type == 'file') {
            if (!isset($array['tmp_name']) || !is_array($array['tmp_name'])) return array();
            else $array = self::unpack($array);
        }

        $r = array();

        $excludeEmptyVars = self::get($args, 'excludeEmpty') == 1;

        foreach ($array as $item) {
            if ($excludeEmptyVars && ($item === null || $item === '')) continue;

            $r[] = VarTools::prepare($item, $type, $args);
        }

        return $r;
    }

    public static function set($array, $key, $value)
    {
        $array[$key] = $value;

        return $array;
    }

    public static function remove($array, $key)
    {
        if (array_key_exists($key, $array)) unset($array[$key]);

        return $array;
    }

    public static function unpack($array)
    {
        if(!$array){
            return array();
        }

        /* Converts
           array(
              key => array(1,2,3,4,5),
              key2 => array('a', 'b', 'c', 'd')
           )
           to
           array(
              array(key => 1, key2 => 'a'), array(key => 2, key2 => 'b'), ...
           )
        */

        $keys = array_keys($array);

        $elementKeys = array_keys($array[current($keys)]);

        $result = array();

        foreach($elementKeys as $elementKey){
            $e = array();
            foreach ($keys as $key)
                $e[$key] = $array[$key][$elementKey];

            $result[$elementKey] = $e;
        }

        return $result;
    }

    public static function flatten($a)
    {
        $res = array();

        if (!is_array($a)) return array($a);
        else if (count($a) == 0) return $res;

        $k = array_keys($a);
        if (is_array($a[$k[0]])) {
            foreach ($a as $ai) {
                $b = self::flatten($ai);
                foreach ($b as $bi) $res[] = $bi;
            }
        } else {
            foreach ($a as $ai) $res[] = $ai;
        }

        return $res;
    }

    public static function mapByKey($array, $key, $groupByKey = false)
    {
        $new = array();

        foreach ($array as $element) {

            $elementKey = is_object($element) ? $element->{$key} : $element[$key];
            if ($groupByKey) {
                if (array_key_exists($elementKey, $new)) $new[$elementKey][] = $element;
                else $new[$elementKey] = array($element);
            } else $new[$elementKey] = $element;
        }

        return $new;
    }

    public static function mapByKeyValue($array, $keyProperty, $valueProperty){
        $new = array();

        foreach($array as $element){
            $new[$element[$keyProperty]] = $element[$valueProperty];
        }

        return $new;
    }

    public static function mergeProperties(&$elements, $properties, $mergeByProperty = false)
    {
        // Copies selection of properties from elements to new array

        $result = array();
        if (!is_array($properties)) $properties = array($properties);

        if ($mergeByProperty) {
            foreach ($properties as $property) $result[$property] = array();
        }

        $isObject = is_object(current($elements));

        // It's faster to execute to different loops for object and arrays than checking the type while iterating
        if ($isObject) {
            foreach ($properties as $property) {
                foreach ($elements as $element) {
                    if ($mergeByProperty) $result[$property][] = $element->{$property};
                    else $result[] = $element->{$property};
                }
            }
        } else {
            foreach ($properties as $property) {
                foreach ($elements as $element) {
                    if ($mergeByProperty) $result[$property][] = $element[$property];
                    else $result[] = $element[$property];
                }
            }
        }

        return $result;
    }

    public static function convertToKeyArray($array, $value = true)
    {
        if (!count($array)) return array();

        // Remove duplicates
        $array = array_flip(array_flip($array));

        return array_combine($array, array_fill(0, count($array), $value));
    }

    public static function implode($array, $glue, $padElementsLeft = '', $padElementsRight = '')
    {
        if (!count($array)) return '';

        $s = '';
        $isFirst = true;
        foreach ($array as $e) {
            if ($isFirst) {
                $isFirst = false;
                $s .= $padElementsLeft . $e . $padElementsRight;
            } else $s .= $glue . $padElementsLeft . $e . $padElementsRight;
        }

        return $s;
    }
}