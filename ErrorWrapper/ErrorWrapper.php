<?php
namespace Cyantree\Grout\ErrorWrapper;

class ErrorWrapper
{
    private static $count = 0;

    public static function register()
    {
        if (!self::$count) {
            set_error_handler(array(__CLASS__, 'onError'));
        }

        self::$count++;
    }

    public static function unregister()
    {
        self::$count--;

        if (!self::$count) {
            restore_error_handler();
        }
    }

    public static function onError($code, $message, $file, $line, $context)
    {
        // Error has been suppressed with @ sign
        if (error_reporting() === 0) {
            return;
        }

        if ($code & E_WARNING || $code & E_USER_WARNING || $code & E_RECOVERABLE_ERROR || $code & E_CORE_WARNING || $code & E_COMPILE_WARNING) {
            $e = new PhpWarningException();

        } elseif ($code & E_NOTICE
            || $code & E_USER_NOTICE
            || $code & E_DEPRECATED
            || $code & E_USER_DEPRECATED
            || $code & E_STRICT
        ) {
            $e = new PhpNoticeException();

        } else {
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
