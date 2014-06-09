<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

class DateTimeContent extends Content
{
    public $format = 'Y-m-d H:i:s';

    /** @var \DateTime */
    protected $_data;

    public function populate($data)
    {

    }

    public function render($mode)
    {
        if ($mode == Set::MODE_EXPORT) {
            return $this->_data->format($this->format);

        } else {
            return '<p>' . StringTools::escapeHtml($this->_data->format($this->format)) . '</p>';
        }
    }
}