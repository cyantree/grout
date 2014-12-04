<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Contents\Renderers\CustomContentRenderer;
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

    protected function getRenderer()
    {
        $renderer = parent::getRenderer();

        if (!$renderer) {
            $renderer = new CustomContentRenderer();
        }

        return $renderer;
    }
}
