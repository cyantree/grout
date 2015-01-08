<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\ContentRendererSettings\ImageContentRendererSettings;
use Cyantree\Grout\Set\Contents\ImageContent;

class ImageContentPlainRenderer extends ContentRenderer
{
    public function render(Content $content)
    {
        /** @var ImageContent $content */
        $data = $content->getValue();

        $url = $content->getImageUrl();

        if ($content->rendererSettings->exportData == ImageContentRendererSettings::EXPORT_URL) {
            return $url;

        } elseif ($content->rendererSettings->exportData == ImageContentRendererSettings::EXPORT_PATH) {
            return $content->getImagePath();

        } elseif ($content->rendererSettings->exportData == ImageContentRendererSettings::EXPORT_VALUE) {
            return $data;

        } else {
            return null;
        }
    }
}
