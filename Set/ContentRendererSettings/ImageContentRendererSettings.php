<?php
namespace Cyantree\Grout\Set\ContentRendererSettings;

class ImageContentRendererSettings
{
    const EXPORT_VALUE = 1;
    const EXPORT_URL = 2;
    const EXPORT_PATH = 3;
    /**
     * @var array display width per set mode. key 'default' is also available
     */
    public $displayWidths = array();
    public $exportData = self::EXPORT_URL;
}
