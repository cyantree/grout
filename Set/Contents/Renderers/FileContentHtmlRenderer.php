<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\FileContent;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class FileContentHtmlRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var FileContent $content */
        $data = $content->getValue();

        $url = $content->getFileUrl();

        $mode = $content->set->mode;

        if ($content->editable && ($mode == Set::MODE_ADD || $mode == Set::MODE_EDIT)) {
            $c = '<input type="file" name="' . $content->name . '" />';

        } else {
            $c = '';
        }

        if ($data) {
            if ($c != '') {
                $c .= '<br /><br />';
            }

            if ($url) {
                $c .= '<a href="' . StringTools::escapeHtml($url) . '" target="_blank">'
                        . StringTools::escapeHtml($url) . '</a>';

            } else {
                $c .= StringTools::escapeHtml($data);
            }
        }

        return $c;
    }
}
