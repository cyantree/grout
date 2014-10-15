<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\CheckboxContentRenderer;

// Fake calls to enable gettext extraction
if (0) {
    _('Das Feld „%name%“ wurde nicht ausgewählt.');
}

class CheckboxContent extends Content
{
    public $required = false;

    public $label;
    public $labelChecked;
    public $labelNotChecked;

    public $value = true;

    public static $errorCodes = array(
        'notSelected' => 'Das Feld „%name%“ wurde nicht ausgewählt.'
    );

    public function getData()
    {
        return $this->data !== null && $this->data !== false;
    }

    public function check()
    {
        if ($this->required && $this->data != $this->value) {
            $this->postError('notSelected', self::$errorCodes['notSelected']);
        }
    }

    public function save()
    {
        $this->data = $this->data == $this->value ? $this->value : null;
    }

    protected function getDefaultRenderer()
    {
        return new CheckboxContentRenderer();
    }
}
