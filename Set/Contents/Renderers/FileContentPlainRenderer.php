<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\FileContent;

class FileContentPlainRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var FileContent $content */
        $data = $content->getValue();

        $url = $content->getFileUrl();

        return $url ? $url : $data;
    }
}
