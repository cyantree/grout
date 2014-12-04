<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\CheckboxContent;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CheckboxContentHtmlRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var CheckboxContent $content */
        
        $isChecked = $content->getValue();
        $mode = $content->set->mode;

        if ($mode == Set::MODE_SHOW
                || $mode == Set::MODE_LIST
                || $mode == Set::MODE_DELETE
                || $mode == Set::MODE_EXPORT
        ) {
            if ($isChecked) {
                return StringTools::escapeHtml($content->labelChecked ? $content->labelChecked : $content->label);

            } else {
                return StringTools::escapeHtml($content->labelNotChecked ? $content->labelNotChecked : '');
            }
        }

        $attributes = $isChecked ? ' checked="checked"' : '';

        if (!$content->editable) {
            $attributes .= ' disabled="disabled"';
        }

        $id = 'c' . StringTools::random(15);

        $c = '<input id="' . $id . '" type="checkbox" name="' . $content->name . '" '
                . 'value="' . StringTools::escapeHtml($content->valueChecked) . '"' . $attributes . ' />';

        if ($content->label != '') {
            $c .= '<label for="' . $id . '">' . StringTools::escapeHtml($content->label) . '</label>';
        }

        return $c;
    }
}
