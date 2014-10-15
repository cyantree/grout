<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\CustomContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CustomContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var CustomContent $content */

        if ($mode == Set::MODE_EXPORT || !$content->escapeContent) {
            return $content->content;

        } else {
            return StringTools::escapeHtml($content->content);
        }
    }
}
