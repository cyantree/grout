<?php
namespace Cyantree\Grout\Tools;

class NamespaceTools{
    public static function getNamespaceOfInstance($instance){
        $class = get_class($instance);
        return substr($class, 0, strrpos($class, '\\'));
    }
}