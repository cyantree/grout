<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\ImageContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class ImageContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var ImageContent $content */
        $data = $content->getData();

        $url = $content->getImageUrl();

        if ($mode == Set::MODE_EXPORT) {
            return $url ? $url : $data;
        }

        if ($content->editable && ($mode == Set::MODE_ADD || $mode == Set::MODE_EDIT)) {
            $c = '<input type="file" name="' . $content->name . '" />';

        } else {
            $c = '';
        }

        if ($data) {
            if ($c) {
                $c .= '<br /><br />';
            }

            if ($url) {
                $c .= '<img id="' . $content->name . '_preview" src="' .
                        StringTools::escapeHtml($url) . '" alt="" />';

            } else {
                $c .= StringTools::escapeHtml($data);
            }
        }

        return $c;
    }
}
