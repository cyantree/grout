<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Tools\StringTools;

class ManualContent extends Content{
    private $_v;

    public function decode($data) {
        $this->_v = $data;
    }

    public function encode() {
        return $this->_v;
    }

    public function render($mode, $namespace = null) {
        return StringTools::escapeHtml($this->_v);
    }

}