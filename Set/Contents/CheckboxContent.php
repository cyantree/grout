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

    public $valueChecked = true;
    public $valueNotChecked = false;

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

    public function getValue()
    {
        return $this->value ? $this->valueChecked : $this->valueNotChecked;
    }

    public function setValue($data)
    {
        $this->value = $data === $this->valueChecked || strval($data) === strval($this->valueChecked);
    }

    public function populate($data, $files)
    {
        /*
         * TODO
         * Overridden. Doesn't use has() because property doesn't get transfered at all
         * when not checking a checkbox in a html form. Maybe this could be configured later.
         */
        $this->setValue($data->get($this->name));
    }

    public function check()
    {
        if ($this->required && !$this->value) {
            $this->postError('notSelected');
        }
    }
}
