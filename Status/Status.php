<?php
namespace Cyantree\Grout\Status;

class Status
{
    const TYPE_PLAIN = 1;
    const TYPE_HTML = 2;

    public $code;
    public $message;
    public $replaces;

    public $escapingContext;
    public $translatorContext;

    function __construct($code = null, $message = null, $replaces = null, $escapingContext = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->replaces = $replaces;
        $this->escapingContext = $escapingContext;
    }
}
