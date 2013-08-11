<?php
namespace Cyantree\Grout\Tools;

class ServerTools
{
    private static $_maxUploadSize;
    private static $_memoryLimit;

    public static function getMemoryLimit(){
        if(self::$_memoryLimit){
            return self::$_memoryLimit;
        }

        self::$_memoryLimit = self::decodePHPConfigSize(ini_get('memory_limit'));
        if(self::$_memoryLimit == -1){
            self::$_memoryLimit = 999 * 1024 * 1024 * 1024;
        }

        return self::$_memoryLimit;
    }

    public static function decodePHPConfigSize($sizeString){
        if(preg_match('!([0-9]+)\s*([A-Z])!', $sizeString, $args)){
            $val = $args[1];

            $type = $args[2];
            if($type == 'K'){
                $val *= 1024;
            }elseif($type == 'M'){
                $val *= 1024 * 1024;
            }elseif($type == 'G'){
                $val *= 1024 * 1024 * 1024;
            }
        }else{
            $val = intval($sizeString);
        }

        return $val;
    }

    public static function getMaxUploadSize(){
        if(self::$_maxUploadSize) return self::$_maxUploadSize;

        $uploadMaxFileSize = self::decodePHPConfigSize(ini_get('upload_max_filesize'));

        $postMaxSize = self::decodePHPConfigSize(ini_get('upload_max_filesize'));

        if($uploadMaxFileSize < $postMaxSize) self::$_maxUploadSize = $uploadMaxFileSize;
        else self::$_maxUploadSize = $postMaxSize;

        return self::$_maxUploadSize;
    }


    public static function setMaxExecutionTime($time)
    {
        ini_alter('max_execution_time', $time);

        return (int)ini_get('max_execution_time');
    }

    public static function getContentTypeByExtension($e, $default = null)
    {
        if ($e == 'gif') return 'image/gif';
        if ($e == 'jpg' || $e == 'jpeg') return 'image/jpeg';
        if ($e == 'png') return 'image/png';
        if ($e == 'js') return 'text/javascript';
        if ($e == 'css') return 'text/css';
        if ($e == 'html' || $e == 'htm') return 'text/html';
        if ($e == 'xml') return 'text/xml';
        if ($e == 'txt' || $e == 'php' || $e == 'php4' || $e == 'php5') return 'text/plain';
        if ($e == 'exe' || $e == 'dmg') return 'application/octet-stream';

        return $default;
    }

    public static function setIncludePath($path)
    {
        if (isset($_SERVER['WINDIR']) || isset($_SERVER['windir']) || isset($_ENV['windir']))
            ini_set('include_path', ini_get('include_path') . ';' . $path);
        else
            ini_set('include_path', ini_get('include_path') . ':' . $path);
    }

    public static function decodeGpcr($get = false, $post = false, $cookie = false, $request = false)
    {
        $magicQuotes = ini_get('magic_quotes_gpc') == 1;
        if (!$magicQuotes) return;

        $process = array();
        if ($get) $process[] = & $_GET;
        if ($post) $process[] = & $_POST;
        if ($cookie) $process[] = & $_COOKIE;
        if ($request) $process[] = & $_REQUEST;

        // Copied from http://php.net/manual/en/security.magicquotes.disabling.php, example 2
        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = & $process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }

        unset($process);
    }

    public static function doNothing()
    {
    }

    public static function suppressErrors($suppress)
    {
        if ($suppress) set_error_handler(array('\Cyantree\Grout\Tools\ServerTools', 'doNothing'));
        else restore_error_handler();
    }

    public static function parseCommandlineString($command)
    {
        $command = trim($command);
        if($command == ''){
            return array('');
        }

        $chars = mb_strlen($command);
        $pos = 0;
        $args = array();
        $currentArgument = '';
        $inQuoteMode = false;

        do{
            $char = mb_substr($command, $pos, 1);

            if($char == '"'){
                $nextChar = $pos < $chars - 1 ? mb_substr($command, $pos + 1, 1) : null;

                if($inQuoteMode){
                    if($nextChar == '"'){
                        $currentArgument .= '"';
                        $pos++;
                    }else{
                        $inQuoteMode = false;
                    }
                }else{
                    $inQuoteMode = true;
                }

            }elseif($char == '\\'){
                $nextChar = $pos < $chars - 1 ? mb_substr($command, $pos + 1, 1) : null;

                if($nextChar == '\\' || $nextChar == '"'){
                    $currentArgument .= $nextChar;
                    $pos++;
                }

            }elseif($char == ' '){
                if($inQuoteMode){
                    $currentArgument .= ' ';
                }else{
                    $args[] = $currentArgument;
                    $currentArgument = '';
                }
            }else{
                $currentArgument .= $char;
            }

            $pos++;
        }while($pos < $chars);

        if($currentArgument !== ''){
            $args[] = $currentArgument;
        }

        if(!count($args)){
            return array('');
        }

        return $args;
    }
}