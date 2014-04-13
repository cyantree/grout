<?php
namespace Cyantree\Grout\Tools;

class DoctrineTools
{
    /** @param $connection \Doctrine\DBAL\Connection */
    public static function prepareQuery($connection, $query, $args = null)
    {
        $argsIsObject = is_object($args);
        if (!$argsIsObject && !is_array($args)) {
            $args = array($args);
        }
        if ($args === null || (!$argsIsObject && !count($args))) {
            $st = $connection->prepare($query);

            return $st;
        }

        $internalCounter = 0;
        $result = '';
        $parts = explode('%', $query);
        $isPara = true;

        $replaces = array();
        $texts = array();

        $params = array();

        foreach ($parts as $part) {
            $isPara = !$isPara;

            if (!$isPara)
                $result .= $part;
            else {
                if ($part === '') $result .= '%';
                else {
                    $part = explode(':', $part, 2);
                    $type = $part[0];
                    if (count($part) > 1) {
                        $index = $part[1];
                    } else {
                        $index = $internalCounter++;
                    }

                    if ($argsIsObject) $val = $args->{$index};
                    else $val = $args[$index];

                    $replaces[] = array($type, $val);
                    $texts[] = $result;
                    $result = '';
                }
            }
        }
        $lastText = $result;

        foreach ($replaces as $key => $replace) {
            if (!is_array($replace)) continue;

            $type = $replace[0];
            $val = $replace[1];
            $replacement = '?';

            $param = array('value' => null, 'type' => 'string');

            if ($type == 'i') {
                $param['type'] = 'integer';
            } // Float
            else if ($type == 'f') {
                $param['type'] = 'float';
            } else if ($type == 'f') {
                $param['type'] = 'float';
            } // Boolean
            else if ($type == 'b') {
                $param['type'] = 'int';
                $param['value'] = intval($val) == 1;
            } // Raw
            else if ($type == 'r') {
                $replacement = $val;
                $param = null;
            } else if ($type == 'd') {
                $param['type'] = 'datetime';
                if (!is_object($val)) {
                    $v = new DateTime();
                    $v->setTimestamp($val);
                    $val = $v;
                }
            } // Integer array
            else if ($type == 'i[]') {
                $param['type'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
            } // Integer array
            else if ($type == 'f[]') {
                $param['type'] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
            } // String array
            else if ($type == 's[]') {
                $param['type'] = \Doctrine\DBAL\Connection::PARAM_STR_ARRAY;
            } // Raw array
            else if ($type == 'r[]') {
                $replacement = implode(',', $val);
                $param = null;
            }

            $replaces[$key] = $replacement;
            if ($param !== null) {
                $param['value'] = $val;
                $params[] = $param;
            }
        }

        $result = '';
        foreach ($texts as $key => $text) {
            $result .= $text . $replaces[$key];
        }


        $st = $connection->prepare($result . $lastText);
        $i = 0;
        foreach ($params as $param) {
            $i++;
            $st->bindValue($i, $param['value'], $param['type']);
        }

        return $st;
    }
}