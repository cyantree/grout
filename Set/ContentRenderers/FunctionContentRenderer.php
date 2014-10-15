<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;

class FunctionContentRenderer extends ContentRenderer
{
    private $callback;
    function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function render(Content $content, $mode)
    {
        return call_user_func($this->callback, $content, $mode);
    }
}
