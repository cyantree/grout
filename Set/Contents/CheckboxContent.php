<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\StringTools;

// Fake calls to enable gettext extraction
if (0) {
    _('Das Feld „%name%“ wurde nicht ausgewählt.');
}

class CheckboxContent extends Content
{
    public $required = false;

    public $label;
    public $value = true;

    private $_value;

    public static $errorCodes = array(
        'notSelected' => 'Das Feld „%name%“ wurde nicht ausgewählt.'
    );

    public function render($mode)
    {
        $attributes = $this->_value == $this->value ? ' checked="checked"' : '';
        if ($mode == Set::MODE_LIST || $mode == Set::MODE_DELETE || !$this->editable) {
            $attributes .= ' disabled="disabled"';
        }

        $id = 'c' . StringTools::random(15);

        $c = '<input id="' . $id . '" type="checkbox" name="' . $this->name . '" value="' . StringTools::escapeHtml($this->value) . '"' . $attributes . ' />';

        if ($this->label != '') {
            $c .= '<label for="' . $id . '">' . StringTools::escapeHtml($this->label) . '</label>';
        }

        return $c;
    }

    public function setData($data)
    {
        $this->_value = $data;
    }

    public function getData()
    {
        return $this->_value !== null && $this->_value !== false;
    }

    public function populate($data)
    {
        $this->_value = $data->get($this->name);
    }

    public function check()
    {
        if ($this->required && $this->_value != $this->value) {
            $this->postError('notSelected', self::$errorCodes['notSelected']);
        }
    }

    public function save()
    {
        $this->_value = $this->_value == $this->value ? $this->value : null;
    }
}