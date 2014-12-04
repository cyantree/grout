<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;

class CheckboxContentSerializableRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        return $content->getValue();
    }
}
