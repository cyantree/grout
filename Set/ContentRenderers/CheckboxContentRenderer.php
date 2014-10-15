<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\CheckboxContent;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CheckboxContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var CheckboxContent $content */
        
        $isChecked = $content->getData();

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
                . 'value="' . StringTools::escapeHtml($content->value) . '"' . $attributes . ' />';

        if ($content->label != '') {
            $c .= '<label for="' . $id . '">' . StringTools::escapeHtml($content->label) . '</label>';
        }

        return $c;
    }
}
