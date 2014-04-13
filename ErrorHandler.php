<?php
namespace Cyantree\Grout;

use Cyantree\Grout\Types\ErrorHandlerError;

class ErrorHandler
{
    private $_callback;
    public $data;
    private $_registered;

    private static $_pool = array();
    private static $_poolLength = 0;

    /** @return ErrorHandler */
    public static function getHandler($callback, $data = null, $register = true)
    {
        if (self::$_poolLength) {
            $h = array_pop(self::$_pool);
            self::$_poolLength--;
        } else {
            $h = new ErrorHandler();
        }

        $h->_callback = $callback;
        $h->data = $data;

        if ($register) $h->register();

        return $h;
    }

    public function register($newData = null)
    {
        if ($this->_registered) return;

        if ($newData !== null) $this->data = $newData;

        set_error_handler(array($this, 'onError'), E_ALL);
        $this->_registered = true;
    }

    public function unRegister()
    {
        if (!$this->_registered) return;

        restore_error_handler();
        $this->_registered = false;
    }

    public function onError($code, $message, $file, $line, $context)
    {
        $e = new ErrorHandlerError();
        $e->code = $code;
        $e->message = $message;
        $e->file = $file;
        $e->line = $line;
        $e->context = $context;
        $e->data = $this->data;

        call_user_func($this->_callback, $e);
    }

    public function destroy()
    {
        $this->unregister();

        $this->_callback = $this->data = null;
        self::$_pool[] = $this;
        self::$_poolLength++;
    }
}