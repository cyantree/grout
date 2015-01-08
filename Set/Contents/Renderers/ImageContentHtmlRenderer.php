<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\ImageContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class ImageContentHtmlRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var ImageContent $content */
        $data = $content->getValue();

        $url = $content->getImageUrl();

        $mode = $content->set->mode;

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
                $width = ArrayTools::get($content->rendererSettings->displayWidths, $mode);
                if ($width == '') {
                    $width = ArrayTools::get($content->rendererSettings->displayWidths, 'default');
                }

                if ($width != '') {
                    $width = ' width="' . $width . '"';
                }

                $c .= '<img id="' . $content->name . '_preview" src="' .
                        StringTools::escapeHtml($url) . '"' . $width . ' alt="" />';

            } else {
                $c .= StringTools::escapeHtml($data);
            }
        }

        return $c;
    }
}
