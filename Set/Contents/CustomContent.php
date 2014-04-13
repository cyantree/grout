<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CustomContent extends Content
{
    public $content;

    public $escapeContent = true;
    public $storeInSet = false;
    public $editable = false;

    public function __construct($content = null, $escapeContent = true, $name = null)
    {
        parent::__construct();

        if ($name === null) {
            $name = StringTools::random(32);
        }

        $this->name = $name;

        $this->content = $content;
        $this->escapeContent = $escapeContent;
    }

    public function render($mode)
    {
        if ($this->escapeContent) {
            return StringTools::escapeHtml($this->content);
        } else {
            return $this->content;
        }
    }
}