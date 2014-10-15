<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\TextContentRenderer;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\StringTools;

// Fake calls to enable gettext extraction
if (0) {
    _('Das Feld „%name%“ darf nicht leer sein.');
    _('Das Feld „%name%“ hat kein gültiges Format.');
    _('Im Feld „%name%“ wurde keine gültige E-Mail-Adresse angegeben.');
    _('Im Feld „%name%“ wurde keine gültige URL angegeben.');
    _('Das Feld „%name%“ darf nicht kürzer als %length% Zeichen sein.');
    _('Das Feld „%name%“ darf nicht länger als %length% Zeichen sein.');
}

class TextContent extends Content
{
    public $multiline = false;

    public $type;
    public $password = false;
    public $minLength = 0;
    public $maxLength = 0;
    public $pattern = null;

    const TYPE_URL = 'url';
    const TYPE_EMAIL = 'email';

    public static $errorCodes = array(
        'invalid' => 'Das Feld „%name%“ darf nicht leer sein.',
        'invalidPattern' => 'Das Feld „%name%“ hat kein gültiges Format.',
        'invalidEmail' => 'Im Feld „%name%“ wurde keine gültige E-Mail-Adresse angegeben.',
        'invalidUrl' => 'Im Feld „%name%“ wurde keine gültige URL angegeben.',
        'minLength' => 'Das Feld „%name%“ darf nicht kürzer als %length% Zeichen sein.',
        'maxLength' => 'Das Feld „%name%“ darf nicht länger als %length% Zeichen sein.'
    );

    protected function getDefaultRenderer()
    {
        return new TextContentRenderer();
    }


    public function getData()
    {
        return $this->data === null ? '' : $this->data;
    }

    public function check()
    {
        $l = mb_strlen($this->data);

        if ($this->required && !$l) {
            $this->postError('invalid', self::$errorCodes['invalid']);
            return;
        }

        if ($this->pattern !== null && !preg_match($this->pattern, $this->data)) {
            $this->postError('invalidPattern', self::$errorCodes['invalidPattern']);
            return;
        }

        if ($this->type == self::TYPE_EMAIL && !StringTools::isEmailAddress($this->data)) {
            $this->postError('invalidEmail', self::$errorCodes['invalidEmail']);
            return;

        } elseif ($this->type == self::TYPE_URL && !StringTools::isUrl($this->data)) {
            $this->postError('invalidUrl', self::$errorCodes['invalidUrl']);
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
}
