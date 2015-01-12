<?php
namespace Cyantree\Grout\Set\ContentRendererSettings;

class ImageContentRendererSettings
{
    const EXPORT_VALUE = 1;
    const EXPORT_URL = 2;
    const EXPORT_PATH = 3;

    public $maxDisplayWidth;
    public $maxDisplayHeight;
    public $exportData = self::EXPORT_URL;
}
