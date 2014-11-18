<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\ContentRendererSettings\ImageContentRendererSettings;
use Cyantree\Grout\Set\Contents\ImageContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

class ImageContentRenderer extends ContentRenderer
{
    /** @var ImageContentRendererSettings */
    public $settings;

    public function __construct(ImageContentRendererSettings $settings = null)
    {
        $this->settings = $settings ? $settings : new ImageContentRendererSettings();
    }


    public function render(Content $content, $mode)
    {
        /** @var ImageContent $content */
        $data = $content->getValue();

        $url = $content->getImageUrl();

        if ($mode == Set::MODE_EXPORT) {
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
                $width = ArrayTools::get($this->settings->displayWidths, $mode);
                if ($width == '') {
                    $width = ArrayTools::get($this->settings->displayWidths, 'default');
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
