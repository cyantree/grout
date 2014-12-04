<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\DateTimeContentRenderer;

class DateTimeContent extends Content
{
    public $format = 'Y-m-d H:i:s';

    /** @var \DateTime */
    protected $value;

    public function populate($data, $files)
    {

    }
}
