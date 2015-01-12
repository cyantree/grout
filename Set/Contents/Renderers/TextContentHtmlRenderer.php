<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class TextContentHtmlRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var TextContent $content */
        $data = $content->getValue();

        $mode = $content->set->mode;

        if ($mode == Set::MODE_SHOW || $mode == Set::MODE_DELETE || $mode == Set::MODE_LIST || !$content->editable) {
            if ($content->type == TextContent::TYPE_HTML) {
                return $data;

            } else {
                return StringTools::escapeHtml($data);
            }
        }

        $additionalAttributes = '';

        if ($content->maxLength) {
            $additionalAttributes .= ' maxlength="'. $content->maxLength . '"';
        }

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
