<?php
namespace Cyantree\Grout\Set\Contents\Renderers;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderer;
use Cyantree\Grout\Set\ContentRendererSettings\ImageContentRendererSettings;
use Cyantree\Grout\Set\Contents\ImageContent;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

abstract class ImageContentRenderer extends ContentRenderer
{
    /** @var ImageContentRendererSettings */
    public $settings;
}
