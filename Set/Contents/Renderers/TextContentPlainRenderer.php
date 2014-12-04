<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\TextContent;

class TextContentPlainRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var TextContent $content */
        return $content->getValue();
    }
}
