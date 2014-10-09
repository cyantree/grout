<?php
namespace Cyantree\Grout;

use Cyantree\Grout\Types\ErrorHandlerError;

class ErrorHandler
{
    private $callback;
    public $data;
    private $registered;

    private static $pool = array();
    private static $poolLength = 0;

    /** @return ErrorHandler */
    public static function getHandler($callback, $data = null, $register = true)
    {
        if (self::$poolLength) {
            $h = array_pop(self::$pool);
            self::$poolLength--;
        } else {
            $h = new ErrorHandler();
        }

        $h->callback = $callback;
        $h->data = $data;

        if ($register) {
            $h->register();
        }

        return $h;
    }

    public function register($newData = null)
    {
        if ($this->registered) {
            return;
        }

        if ($newData !== null) {
            $this->data = $newData;
        }

        set_error_handler(array($this, 'onError'), E_ALL);
        $this->registered = true;
    }

    public function unRegister()
    {
        if (!$this->registered) {
            return;
        }

        restore_error_handler();
        $this->registered = false;
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

        call_user_func($this->callback, $e);
    }

    public function destroy()
    {
        $this->unregister();

        $this->callback = $this->data = null;
        self::$pool[] = $this;
        self::$poolLength++;
    }
}
