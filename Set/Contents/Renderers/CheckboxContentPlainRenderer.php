<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\CheckboxContent;

class CheckboxContentPlainRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var CheckboxContent $content */
        
        $isChecked = $content->getValue();

        return $isChecked ? $content->labelChecked : $content->labelNotChecked;
    }
}
