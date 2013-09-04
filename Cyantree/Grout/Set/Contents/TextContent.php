<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

// Fake calls to enable gettext extraction
if (0) {
    _('Das Feld „%name%“ darf nicht leer sein.');
    _('Das Feld „%name%“ hat kein gültiges Format.');
    _('Im Feld „%name%“ wurde keine gültige E-Mail-Adresse angegeben.');
    _('Im Feld „%name%“ wurde keine gültige URL angegeben.');
    _('Im Feld „%name%“ wurde keine gültige Zahl angegeben.');
    _('Das Feld „%name%“ darf nicht kürzer als %length% Zeichen sein.');
    _('Das Feld „%name%“ darf nicht länger als %length% Zeichen sein.');
}

class TextContent extends Content
{
    protected $_value;

    public $multiline = false;

    public $type;
    public $password = false;
    public $minLength = 0;
    public $maxLength = 0;
    public $pattern = null;

    public $stringDomain = 'admin';

    const TYPE_URL = 'url';
    const TYPE_EMAIL = 'email';
    const TYPE_NUMBER_INT = 'number_int';
    const TYPE_NUMBER_FLOAT = 'number_float';

    public static $errorCodes = array(
        'invalid' => 'Das Feld „%name%“ darf nicht leer sein.',
        'invalidPattern' => 'Das Feld „%name%“ hat kein gültiges Format.',
        'invalidEmail' => 'Im Feld „%name%“ wurde keine gültige E-Mail-Adresse angegeben.',
        'invalidUrl' => 'Im Feld „%name%“ wurde keine gültige URL angegeben.',
        'invalidNumber' => 'Im Feld „%name%“ wurde keine gültige Zahl angegeben.',
        'minLength' => 'Das Feld „%name%“ darf nicht kürzer als %length% Zeichen sein.',
        'maxLength' => 'Das Feld „%name%“ darf nicht länger als %length% Zeichen sein.'
    );

    public function decode($data)
    {
        $this->_value = $data;
    }

    public function encode()
    {
        return $this->_value;
    }

    public function populate($data)
    {
        $this->_value = $data->get($this->name);
    }

    public function check()
    {
        $l = mb_strlen($this->_value);

        if ($this->required && !$l) {
            $this->postError('invalid', self::$errorCodes['invalid']);
            return;
        }

        if ($this->pattern !== null && !preg_match($this->pattern, $this->_value)) {
            $this->postError('invalidPattern', self::$errorCodes['invalidPattern']);
            return;
        }

        if ($this->type == self::TYPE_EMAIL && !StringTools::isMailAddress($this->_value)) {
            $this->postError('invalidEmail', self::$errorCodes['invalidEmail']);
            return;

        } elseif ($this->type == self::TYPE_URL && !StringTools::isUrl($this->_value)) {
            $this->postError('invalidUrl', self::$errorCodes['invalidUrl']);
            return;
        } elseif ($this->type == self::TYPE_NUMBER_INT && (!is_numeric($this->_value)
                || !(is_numeric($this->_value) && (intval($this->_value) == floatval($this->_value))))
        ) {
            $this->postError('invalidNumber', self::$errorCodes['invalidNumber']);
            return;
        } elseif ($this->type == self::TYPE_NUMBER_FLOAT && !is_numeric($this->_value)) {
            $this->postError('invalidNumber', self::$errorCodes['invalidNumber']);
            return;
        }

        if ($this->minLength || $this->maxLength) {
            $code = $length = null;

            if ($this->minLength && $l < $this->minLength) {
                $code = 'minLength';
                $length = $this->minLength;
            } elseif ($this->maxLength && $l > $this->maxLength) {
                $code = 'maxLength';
                $length = $this->maxLength;
            }

            if ($code) {
                $this->postError($code, self::$errorCodes[$code], array('%length%' => $length));
                return;
            }
        }
    }

    public function save()
    {
    }

    public function render($mode)
    {
        if ($mode == Set::MODE_DELETE || $mode == Set::MODE_LIST || !$this->editable) {
            return '<p>' . StringTools::escapeHtml($this->_value) . '</p>';
        }

        if ($this->password) {
            return '<input type="password" name="' . $this->name . '" value="" />';
        }

        if ($this->multiline) {
            return '<textarea name="' . $this->name . '">' . StringTools::escapeHtml($this->_value) . '</textarea>';
        }

        return '<input type="text" name="' . $this->name . '" value="' . StringTools::escapeHtml(
            $this->_value
        ) . '" />';
    }
}