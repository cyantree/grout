<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\ListContentRenderer;

// Fake calls to enable gettext extraction
if (0) {
    _('Im Feld „%name%“ wurde keine Option gewählt.');
}

class ListContent extends Content
{
    public $options = array();

    public static $errorCodes = array(
        'invalid' => 'Im Feld „%name%“ wurde keine Option gewählt.'
    );

    public function check()
    {
        if (!isset($this->options[$this->data])) {
            $this->postError('invalid', self::$errorCodes['invalid']);
        }
    }

    protected function getDefaultRenderer()
    {
        return new ListContentRenderer();
    }
}
