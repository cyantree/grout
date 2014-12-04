<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRendererSettings\ImageContentRendererSettings;
use Cyantree\Grout\Set\Contents\ImageContent;

class ImageContentSerializableRenderer extends ImageContentRenderer
{
    public function render(Content $content)
    {
        /** @var ImageContent $content */
        $data = $content->getValue();

        $url = $content->getImageUrl();

        if ($this->settings->exportData == ImageContentRendererSettings::EXPORT_URL) {
            return $url;

        } elseif ($this->settings->exportData == ImageContentRendererSettings::EXPORT_PATH) {
            return $content->getImagePath();

        } elseif ($this->settings->exportData == ImageContentRendererSettings::EXPORT_VALUE) {
            return $data;

        } else {
            return null;
        }
    }
}
