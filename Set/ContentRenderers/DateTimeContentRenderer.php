<?php
namespace Cyantree\Grout\Set\ContentRenderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\Contents\DateTimeContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class DateTimeContentRenderer extends ContentRenderer
{
    public function render(Content $content, $mode)
    {
        /** @var DateTimeContent $content */

        /** @var \DateTime $data */
        $data = $content->getValue();

        $date = $data ? $data->format($content->format) : '';

        if ($mode == Set::MODE_EXPORT) {
            return $date;

        } else {
            return '<p>' . StringTools::escapeHtml($date) . '</p>';
        }
    }
}
