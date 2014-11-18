<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\ListContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class ListContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var ListContent $content */
        $data = $content->getValue();

        if ($mode == Set::MODE_EXPORT) {
            return $content->options[$data];
        }

        if ($mode == Set::MODE_SHOW || $mode == Set::MODE_DELETE || $mode == Set::MODE_LIST || !$content->editable) {
            return '<p>' . StringTools::escapeHtml($content->options[$data]) . '</p>';

        } elseif ($mode == Set::MODE_EDIT || $mode == Set::MODE_ADD) {

            $c = '<select name="' . $content->name . '">';

            $data = strval($data);
            foreach ($content->options as $key => $value) {
                $selected = strval($key) === $data ? ' selected="selected"' : '';
                $c .= '<option value="' . StringTools::escapeHtml($key) . '"' . $selected . '>'
                        . StringTools::escapeHtml($value) . '</option>';
            }

            $c .= '</select>';
            return $c;
        }

        return $content->options[$data];
    }
}
