<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\CustomContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class CustomContentRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var CustomContent $content */

        if ($content->set->format == Set::FORMAT_HTML && $content->escapeContent) {
            return StringTools::escapeHtml($content->content);

        } else {
            return $content->content;
        }
    }
}
