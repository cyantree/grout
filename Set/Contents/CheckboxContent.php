<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\CheckboxContentRenderer;

class CheckboxContent extends Content
{
    public $required = false;

    public $label;
    public $labelChecked;
    public $labelNotChecked;

    public $value = true;

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                'notSelected' => _('Das Feld „%name%“ wurde nicht ausgewählt.')
            ));
        }

        return $errors->get($code);
    }

    public function getData()
    {
        return $this->data !== null && $this->data !== false;
    }

    public function check()
    {
        if ($this->required && $this->data != $this->value) {
            $this->postError('notSelected');
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
