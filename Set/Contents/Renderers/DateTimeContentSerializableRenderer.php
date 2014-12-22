<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\DateTimeContent;

class DateTimeContentSerializableRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var DateTimeContent $content */

        return $content->getValue();
    }
}
