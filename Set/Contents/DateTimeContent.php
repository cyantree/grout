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

    public function populate($data, $files)
    {

    }

    public function render($mode)
    {
        $date = $this->_data ? $this->_data->format($this->format) : '';

        if ($mode == Set::MODE_EXPORT) {
            return $date;

        } else {
            return '<p>' . StringTools::escapeHtml($date) . '</p>';
        }
    }
}