<?php
namespace Cyantree\Grout\ErrorWrapper;

class ErrorWrapper
{
    private static $_count = 0;

    public static function register()
    {
        if(!self::$_count){
            set_error_handler(array(__CLASS__, 'onError'));
        }

        self::$_count++;
    }

    public static function unregister()
    {
        self::$_count--;

        if(!self::$_count){
            restore_error_handler();
        }
    }

    public static function onError($code, $message, $file, $line, $context){
        if($code & E_WARNING || $code & E_USER_WARNING){
            $e = new PhpWarningException();
        }elseif($code & E_NOTICE || $code & E_USER_NOTICE || $code & E_DEPRECATED || $code & E_USER_DEPRECATED || $code & E_STRICT){
            $e = new PhpNoticeException();
        }else{
            $e = new PhpErrorException();
        }

        $e->setCode($code);
        $e->setMessage($message);
        $e->setFile($file);
        $e->setLine($line);
        $e->setContext($context);

        throw $e;
    }
}