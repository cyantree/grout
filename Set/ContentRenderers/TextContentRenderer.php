<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class TextContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var TextContent $content */
        $data = $content->getData();

        if ($mode == Set::MODE_EXPORT) {
            return $data;
        }

        if ($mode == Set::MODE_SHOW || $mode == Set::MODE_DELETE || $mode == Set::MODE_LIST || !$content->editable) {
            return '<p>' . StringTools::escapeHtml($data) . '</p>';
        }


        $additionalAttributes = '';

        $attributes = $content->config->get('attributes');
        if ($attributes) {
            foreach ($attributes as $key => $value) {
                $additionalAttributes .= " {$key}=\"" . StringTools::escapeHtml($value) . "\"";
            }
        }

        if ($content->password) {
            return '<input type="password" name="' . $content->name . '" value=""' . $additionalAttributes . ' />';
        }

        if ($content->multiline) {
            return '<textarea name="' . $content->name . '"' . $additionalAttributes . '>'
            . StringTools::escapeHtml($data) . '</textarea>';
        }

        return '<input type="text" name="' . $content->name . '" '
        . 'value="' . StringTools::escapeHtml($data) . '"' . $additionalAttributes . ' />';
    }
}
