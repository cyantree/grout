<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\FileContent;
use Cyantree\Grout\Set\Contents\TextContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class FileContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var FileContent $content */
        $data = $content->getData();

        $url = $content->getFileUrl();

        if ($mode == Set::MODE_EXPORT) {
            return $url ? $url : $data;
        }

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
