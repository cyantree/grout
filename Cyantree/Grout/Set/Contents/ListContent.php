<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

// Fake calls to enable gettext extraction
if(0){
    _('Im Feld „%name%“ wurde keine Option gewählt.');
}

class ListContent extends Content{
    protected $_value;

    public $options = array();

    public static $errorCodes = array(
        'invalid' => 'Im Feld „%name%“ wurde keine Option gewählt.'
    );

    public function decode($data) {
        $this->_value = $data;
    }

    public function encode() {
        return $this->_value;
    }

    public function populate($data) {
        $this->_value = $data->get($this->name);
    }

    public function check() {
        if(!isset($this->options[$this->_value])){
            $this->postError('invalid', self::$errorCodes['invalid']);
        }
    }

    public function save() {
    }

    public function render($mode) {
        if($mode == Set::MODE_DELETE || $mode == Set::MODE_LIST || !$this->editable){
            return '<p>'.StringTools::escapeHtml($this->options[$this->_value]).'</p>';

        }elseif($mode == Set::MODE_EDIT || $mode == Set::MODE_ADD){

            $c = '<select name="'.$this->name.'">';

            foreach($this->options as $key => $value){
                $selected = $key === $this->_value ? ' selected="selected"' : '';
                $c .= '<option value="'.StringTools::escapeHtml($key).'"'.$selected.'>'.StringTools::escapeHtml($value).'</option>';
            }

            $c .= '</select>';
            return $c;
        }

        return $this->options[$this->_value];
    }
}