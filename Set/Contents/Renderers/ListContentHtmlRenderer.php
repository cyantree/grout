<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\ListContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class ListContentHtmlRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var ListContent $content */
        $data = $content->getValue();

        $mode = $content->set->mode;

        if ($content->editable && ($mode == Set::MODE_EDIT || $mode == Set::MODE_ADD)) {
            $c = '<select name="' . $content->name . '">';

            $data = strval($data);
            foreach ($content->options as $key => $value) {
                $selected = strval($key) === $data ? ' selected="selected"' : '';
                $c .= '<option value="' . StringTools::escapeHtml($key) . '"' . $selected . '>'
                        . StringTools::escapeHtml($value) . '</option>';
            }

            $c .= '</select>';
            return $c;

        } else {
            return '<p>' . StringTools::escapeHtml($content->options[$data]) . '</p>';
        }
    }
}
