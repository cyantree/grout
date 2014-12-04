<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\ListContent;

class ListContentPlainRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var ListContent $content */
        $data = $content->getValue();

        return $content->options[$data];
    }
}
