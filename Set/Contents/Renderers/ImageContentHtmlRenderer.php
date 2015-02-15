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

            $url = $content->getImageUrl();

            if ($url) {
                $maxDisplayWidth = $content->rendererSettings->maxDisplayWidth;
                $maxDisplayHeight = $content->rendererSettings->maxDisplayHeight;

                $styles = array();
                if ($maxDisplayWidth) {
                    $styles[] = 'max-width:' . $maxDisplayWidth . 'px';
                }

                if ($maxDisplayHeight) {
                    $styles[] = 'max-height:' . $maxDisplayHeight . 'px';
                }

                $attributes = '';

                if ($styles) {
                    $attributes .= ' style="' . implode(';', $styles) . '"';
                }

                $c .= "<img src=\"{$url}\"{$attributes} />";

            } else {
                $c .= StringTools::escapeHtml($data);
            }
        }

        return $c;
    }
}
