<?php
namespace Cyantree\Grout\Tools;

use Doctrine\DBAL\Connection;

class DoctrineTools
{
    /** @param $connection Connection */
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

            if (!$isPara) {
                $result .= $part;

            } else {
                if ($part === '') {
                    $result .= '%';

                } else {
                    $part = explode(':', $part, 2);
                    $type = $part[0];
                    if (count($part) > 1) {
                        $index = $part[1];

                    } else {
                        $index = $internalCounter++;
                    }

                    if ($argsIsObject) {
                        $val = $args->{$index};

                    } else {
                        $val = $args[$index];
                    }

                    $replaces[] = array($type, $val);
                    $texts[] = $result;
                    $result = '';
                }
            }
        }
        $lastText = $result;

        foreach ($replaces as $key => $replace) {
            if (!is_array($replace)) {
                continue;
            }

            $type = $replace[0];
            $val = $replace[1];
            $replacement = '?';

            $param = array('value' => null, 'type' => 'string');

            if ($type == 'i') {
                $param['type'] = 'integer';

            } elseif ($type == 'f') {
                // Float
                $param['type'] = 'float';

            } elseif ($type == 'b') {
                // Boolean
                $param['type'] = 'int';
                $param['value'] = intval($val) == 1;

            } elseif ($type == 'r') {
                // Raw
                $replacement = $val;
                $param = null;

            } elseif ($type == 'd') {
                $param['type'] = 'datetime';
                if (!is_object($val)) {
                    $v = new \DateTime();
                    $v->setTimestamp($val);
                    $val = $v;
                }

            } elseif ($type == 'i[]') {
                // Integer array
                $param['type'] = Connection::PARAM_INT_ARRAY;

            } elseif ($type == 'f[]') {
                // Float array
                $param['type'] = Connection::PARAM_STR_ARRAY;

            } elseif ($type == 's[]') {
                // String array
                $param['type'] = Connection::PARAM_STR_ARRAY;

            } elseif ($type == 'r[]') {
                // Raw array
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
