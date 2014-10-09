<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\Types\FileUpload;

class VarTools
{
    public static function prepare($data, $type, $args = null)
    {
        // file, int, float, bool, string, line, raw, sql, sqlite

        if ($type === null) {
            $type = 'raw';
        }

        $len = strlen($type);
        $isArray = substr($type, $len - 2, 2) == '[]';

        if ($isArray) {
            return ArrayTools::prepare($data, substr($type, 0, $len - 2), $args);
        }

        /*
         * int, float: args = min,max
         * string, line, raw: args = length
         * sql, sqlite: args = encapsulate
         */

//		if($data === null) return null;
        if (is_resource($data) || is_object($data)) {
            return null;
        }

        if ($type == 'file') {
            if (!is_array($data)) {
                return null;
            }
            if (!isset($data['tmp_name']) || is_array($data['tmp_name']) || $data['error'] == 4) {
                return null;
            }

        } elseif (is_array($data)) {
            return null;
        }

        if ($type == 'file') {
            $u = new FileUpload();
            $u->name = $data['name'];
            $u->file = $data['tmp_name'];
            $u->size = $data['size'];
            $u->error = $data['error'];

            return $u;

        } else {
            $typeIsString = $type == 'raw' || $type == 'line' || $type == 'string' || $type == 'sql' || $type == 'sqlite';
            $typeIsNumber = !$typeIsString && ($type == 'int' || $type == 'float');

            $padLeft = $typeIsString ? ArrayTools::get($args, 'padLeft') : null;
            $padRight = $typeIsString ? ArrayTools::get($args, 'padRight') : null;

            $length = $min = $max = 0;

            if ($typeIsString) {
                $length = ArrayTools::get($args, 'length');

            } elseif ($typeIsNumber) {
                $min = ArrayTools::get($args, 'min');
                $max = ArrayTools::get($args, 'max');
            }

            if ($type == 'sql') {
                $data = mysql_real_escape_string($data);

            } elseif ($type == 'sqlite') {
                $data = str_replace('"', '""', $data);

            } elseif ($type == 'int') {
                $data = intval(
                    $data
                );

            } elseif ($type == 'float') {
                $data = floatval(
                    $data
                );

            } elseif ($type == 'bool') {
                $data = $data == '1' || $data == 'true';

            } else {
                $data = trim($data);
                $data = preg_replace('/[\x00-\x08]/', '', $data);

                if ($type == 'line') {
                    $data = str_replace(array("\r", "\n", "\t"), array('', '', ''), $data);
                }
            }


            if ($typeIsNumber && ($min !== null || $max !== null)) {
                $data = NumberTools::limit($data, $min, $max);
            }
            if ($typeIsString) {
                if ($length && mb_strlen($data) > $length) {
                    $data = mb_substr($data, 0, $length);
                }
                if ($padLeft !== null) {
                    $data = $padLeft . $data;
                }
                if ($padRight !== null) {
                    $data .= $padRight;
                }
            }

            return $data;
        }
    }
}
