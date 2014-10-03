<?php
namespace Cyantree\Grout\Tools;

class StringTools
{
    private static $_randomChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function formatFilesize($filesize, $decimals = 1, $decimalPoint = '.')
    {
        $filesize = intval($filesize);

        if ($filesize < 1024) {
            return $filesize . ' B';
        }

        $factor = pow(10, $decimals);
        if ($filesize < 1024 * 1024)
            $size = (round($filesize / 1024 * $factor) / $factor) . ' KB';
        else $size = (round($filesize / 1024 / 1024 / $factor) * $factor) . ' MB';

        if ($decimalPoint != '.') return str_replace('.', $decimalPoint, $size);

        return $size;
    }

    public static function escapeHtml($string)
    {
        return str_replace(array('&', '<', '>', '"', "'"), array('&amp;', '&lt;', '&gt;', '&quot;', '&#039;'), $string);
    }

    public static function escapeJs($string)
    {
        return addcslashes($string, "\r\n\"\\'");
    }

    public static function escapeMySql($string)
    {
        return str_replace(array('\\', '"', chr(0), chr(10), chr(13), chr(26), "'"), array('\\\\', '\\"', '\\0', '\\n', '\\r', '\\Z', '\\\''), $string);
    }

    public static function escapeSqlite($string)
    {
        return str_replace(array('"', chr(0)), array('""', ''), $string);
    }

    public static function escapeRaw($text)
    {
        return str_replace("\\", "\\\\", $text);
    }

    public static function escapeHtmlMultiLineText($text, $allowEmptyParagraphs = false, $escapeParagraphs = true, $paragraphTag = 'div')
    {
        $c = '';
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        $text = explode("\n", $text);
        foreach ($text as $textI) {
            $textI = trim($textI);
            if ($textI != '') {
                if ($escapeParagraphs) $textI = self::escapeHtml($textI);
                $c .= '<' . $paragraphTag . '>' . $textI . '</' . $paragraphTag . '>' . chr(10);
            } else if ($allowEmptyParagraphs) {
                if ($paragraphTag == 'p') $c .= '<p>&nbsp;</p>' . chr(10);
                else $c .= '<' . $paragraphTag . '></' . $paragraphTag . '>' . chr(10);
            }
        }

        return $c;
    }

    public static function random($length, $chars = null)
    {
        if ($chars == null)
            $chars = & self::$_randomChars;

        $r = '';

        $sc = mb_strlen($chars) - 1;

        while ($length--)
            $r .= mb_substr($chars, mt_rand(0, $sc), 1);

        return $r;
    }

    public static function camelCase($string, $separator = ' ')
    {
        $parts = explode($separator, $string);

        $string = '';
        foreach ($parts as $part) $string .= ucfirst($part);

        return $string;
    }

    /** Helper function by "limalopex.eisfux.de7"
     * Original function name: foxy_utf8_to_nce()
     * http://de.php.net/manual/de/function.imagettftext.php#57416
     */
    public static function toNumericEntities($utf)
    {

        $max_count = 5; // flag-bits in $max_mark ( 1111 1000 == 5 times 1)
        $max_mark = 248; // marker for a (theoretical ;-)) 5-byte-char and mask for a 4-byte-char;

        $html = '';
        for ($str_pos = 0; $str_pos < strlen($utf); $str_pos++) {
            $old_chr = $utf{$str_pos};
            $old_val = ord($old_chr);

            $utf8_marker = 0;

            // skip non-utf-8-chars
            if ($old_val > 127) {
                $mark = $max_mark;
                for ($byte_ctr = $max_count; $byte_ctr > 2; $byte_ctr--) {
                    // actual byte is utf-8-marker?
                    if (($old_val & $mark) == (($mark << 1) & 255)) {
                        $utf8_marker = $byte_ctr - 1;
                        break;
                    }
                    $mark = ($mark << 1) & 255;
                }
            }

            // marker found: collect following bytes
            if ($utf8_marker > 1 and isset($utf{$str_pos + 1})) {
                $str_off = 0;
                $new_val = $old_val & (127 >> $utf8_marker);
                for ($byte_ctr = $utf8_marker; $byte_ctr > 1; $byte_ctr--) {

                    // check if following chars are UTF8 additional data blocks
                    // UTF8 and ord() > 127
                    if ((ord($utf{$str_pos + 1}) & 192) == 128) {
                        $new_val = $new_val << 6;
                        $str_off++;
                        // no need for Addition, bitwise OR is sufficient
                        // 63: more UTF8-bytes; 0011 1111
                        $new_val = $new_val | (ord($utf{$str_pos + $str_off}) & 63);
                    } // no UTF8, but ord() > 127
                    // nevertheless convert first char to NCE
                    else {
                        $new_val = $old_val;
                    }
                }
                // build NCE-Code
                $html .= '&#' . $new_val . ';';
                // Skip additional UTF-8-Bytes
                $str_pos = $str_pos + $str_off;
            } else {
                $html .= $old_chr;
            }
        }

        return ($html);
    }

    public static function toUrlPart($string)
    {
        $string = str_replace(
            array(
                'ä',
                'ö',
                'ü',
                'ß',
                'Ä',
                'Ö',
                'Ü',
                'á',
                'à',
                'â',
                'Á',
                'À',
                'Â',
                'é',
                'è',
                'ê',
                'É',
                'È',
                'Ê',
                'í',
                'ì',
                'î',
                'Í',
                'Ì',
                'Î',
                'ó',
                'ò',
                'ô',
                'Ó',
                'Ò',
                'Ô',
                'ú',
                'ù',
                'û',
                'Ú',
                'Ù',
                'Û'
            ),
            array(
                'a',
                'o',
                'u',
                'ss',
                'A',
                'O',
                'U',
                'a',
                'a',
                'a',
                'A',
                'A',
                'A',
                'e',
                'e',
                'e',
                'E',
                'E',
                'E',
                'i',
                'i',
                'i',
                'I',
                'I',
                'I',
                'o',
                'o',
                'o',
                'O',
                'O',
                'O',
                'u',
                'u',
                'u',
                'U',
                'U',
                'U'
            ),
            $string
        );
        $string = str_replace(
            array("\n", "\r", "\t", '"', "'", '“', '„', '”', ',', '.', ' ', '/', '\\', ':', '+', '@', '=', ';', '&'),
            array('-', '', '', '', '', '', '', '', '-', '-', '-', '-', '-', '-', '-', '-' . '-' . '-', '-'),
            $string
        );
        $string = urlencode($string);
        $string = preg_replace('/%[0-9a-fA-F]{2}/', '', $string);
        $string = preg_replace('/-+/', '-', $string);
        $string = trim($string, '-');

        return $string;
    }

    public static function escapeEreg($string)
    {
        return str_replace(
            array('\\', '@', '[', ']', '(', ')', '.', '*', '?', '+', '^', '$', '{', '}', '-'),
            array('\\\\', '\@', '\\[', '\\]', '\\(', '\\)', '\\.', '\\*', '\\?', '\\+', '\\^', '\\$', '\\{', '\\}', '\\-'),
            $string
        );
    }

    public static function replaceSection($string, $start, $end, $replaceString, $trimStart = false, $trimEnd = false)
    {
        $searchStart = ($trimStart ? '[[:space:][:cntrl:]]*' : '') . self::escapeEreg($start);
        $searchEnd = self::escapeEreg($end) . ($trimEnd ? '[[:space:][:cntrl:]]*+' : '');

        return preg_replace('@' . $searchStart . '.*' . $searchEnd . '@sU', $replaceString, $string);
    }

    /**
     * @param array $args
     * @param bool $questionMarkWhenNeeded
     * @return string
     */
    public static function getQueryString($args, $questionMarkWhenNeeded = true)
    {
        if ($args == null || !count($args)) return '';

        return ($questionMarkWhenNeeded ? '?' : '') . http_build_query($args, '', '&');
    }

    public static function md5($data, $salt = '', $extraRounds = 10000)
    {
        $data = md5($data . $salt);

        if ($extraRounds == 0) {
            return $data;
        }

        for ($i = 0; $i < $extraRounds; $i++) {
            $data = $data . $salt;
            $data .= $i & 1 ? substr($data, 0, 16) : substr($data, 16, 16);
            $data = md5($data);
        }

        return $data;
    }

    public static function parse($string, $args, $filterCallback = null)
    {
        $argsIsObject = is_object($args);
        if (!$argsIsObject && !is_array($args)) $args = array($args);
        if ($args === null || (!$argsIsObject && !count($args))) return $string;

        $internalCounter = 0;
        $result = '';
        $parts = explode('%', $string);
        $isPara = true;

        $replaces = array();
        $texts = array();
        foreach ($parts as $part) {
            $isPara = !$isPara;

            if (!$isPara)
                $result .= $part;
            else {
                if ($part === '') $result .= '%';
                else {
                    $type = $part[0];
                    if (strlen($part) > 1) {
                        $index = mb_substr($part, 1);
                        if (substr($index, 0, 1) == ':') $index = substr($index, 1);
                    } else
                        $index = $internalCounter++;

                    if ($argsIsObject) $val = $args->{$index};
                    else $val = $args[$index];

                    $replaces[] = array($type, $val);
                    $texts[] = $result;
                    $result = '';
                }
            }
        }
        $lastText = $result;

        if ($replaces && $filterCallback != null) $replaces = call_user_func($filterCallback, $replaces);

        foreach ($replaces as $key => $replace) {
            if (!is_array($replace)) continue;

            $type = $replace[0];
            $val = $replace[1];

            if ($type == 'i')
                $val = intval($val);

            // Float
            else if ($type == 'f')
                $val = floatval($val);

            // Boolean
            else if ($type == 'b')
                $val = intval($val);

            // Integer array
            else if ($type == 'I')
                $val = implode(',', ArrayTools::prepare($val, 'int', array('excludeEmpty' => true)));

            // Integer array
            else if ($type == 'F')
                $val = implode(',', ArrayTools::prepare($val, 'float', array('excludeEmpty' => true)));

            // Raw string array
            else if ($type == 'R')
                $val = implode(',', $val);

            $replaces[$key] = $val;
        }

        $result = '';
        foreach ($texts as $key => $text) {
            $result .= $text . $replaces[$key];
        }


        return $result . $lastText;
    }

    public static function isEmailAddress($mail)
    {
        return filter_var($mail, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function isUrl($url, $allowedProtocols = null)
    {
        $isURL = filter_var($url, FILTER_VALIDATE_URL) !== false;

        if (!$isURL) return false;
        else if ($allowedProtocols === null) return true;

        $protocol = strtolower(substr($url, 0, strpos($url, '://')));

        if (is_string($allowedProtocols)) return $protocol == $allowedProtocols;
        else return in_array($protocol, $allowedProtocols);
    }

    public static function isJunkMailAddress($mail)
    {
        return preg_match('/@(trash\-mail\.com|sofort\-mail\.de|spoofmail\.de|spambog\.com|wegwerfmail\.com|wegwerfemail\.de|dodgeit\.com|mailinator\.com|jetable\.org|spam\.la|mytrashmail\.com|discardmail\.com|e4ward\.com|spamgourmet\.com|trashmail\.ws|spambog\.de|spambog\.ru|discardmail\.de|cust\.in|imails\.info|teewars\.org|0815\.ru|s0ny\.net|politikerclub\.de|mypartyclip\.de|hochsitze\.com|hulapla\.de|fr33mail\.de|m4ilweb\.info|nospamthanks\.info|webm4il\.info)$/', strtolower($mail)) == 1;
    }

    public static function isRobotUserAgent($browser)
    {
        return preg_match("/cronBROWSE|Googlebot|Baiduspider|bingbot|Sogou\-Test\-Spider|Yahoo|MSN|msn|SheenBot|aiHitBot|NetcraftSurvey|Sosospider|DotBot|Yandex|Gigabot|StackRambler|speedy_spider|seoprofiler|sogou|80legs|VoilaBot|Yeti|\.attentio\.com|Cligoo|Domnutch|oBot|\.ask\.com|Ezooms|Exabot|MJ12bot|DomainCrawler|HuaweiSymantecSpider|SeznamBot/", $browser);
    }

    public static function isHex($txt)
    {
        return preg_match('/^[0-9a-f]+$/', $txt) == 1;
    }

    public static function isMd5($txt)
    {
        return preg_match('/^[0-9a-f]{32}$/', $txt) == 1;
    }

    public static function calculatePasswordStrength($pw)
    {
        $strength = 0;
        $chars = array();

        $specialChars = self::escapeEreg('"\'!§$%&/()=?\\}][{@,;.:-_#+*~<>');
        $charClass_az_used = false;
        $charClass_AZ_used = false;
        $charClass_09_used = false;
        $charClass_special_used = false;
        $charClass_extra_used = false;

        $differentCharsUsed = 0;


        $count = mb_strlen($pw);
        for ($i = 0; $i < $count; $i++) {
            $char = mb_substr($pw, $i, 1);

            if (!isset($chars[$char])) {
                $differentCharsUsed++;
                $chars[$char] = true;
            }

            if (preg_match('@[a-z]@', $char)) $charClass_az_used = true;
            if (preg_match('@[A-Z]@', $char)) $charClass_AZ_used = true;
            if (preg_match('@[0-9]@', $char)) $charClass_09_used = true;
            if (preg_match('@[' . $specialChars . '.]@', $char)) {
                $charClass_special_used = true;
                $differentCharsUsed++;
            } else {
                $charClass_extra_used = true;
            }
        }

        if ($charClass_az_used) $strength += 1;
        if ($charClass_AZ_used) $strength += 1;
        if ($charClass_09_used) $strength += 1;
        if ($charClass_special_used) $strength += 2;
        if ($charClass_extra_used) $strength += 2;
        $strength += $differentCharsUsed / 4;

        $strength += $count / 8;


        return $strength;
    }
}