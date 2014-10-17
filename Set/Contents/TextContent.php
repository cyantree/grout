<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\TextContentRenderer;
use Cyantree\Grout\Tools\StringTools;

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

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                    'invalid' => _('Das Feld „%name%“ darf nicht leer sein.'),
                    'invalidPattern' => _('Das Feld „%name%“ hat kein gültiges Format.'),
                    'invalidEmail' => _('Im Feld „%name%“ wurde keine gültige E-Mail-Adresse angegeben.'),
                    'invalidUrl' => _('Im Feld „%name%“ wurde keine gültige URL angegeben.'),
                    'minLength' => _('Das Feld „%name%“ darf nicht kürzer als %length% Zeichen sein.'),
                    'maxLength' => _('Das Feld „%name%“ darf nicht länger als %length% Zeichen sein.')
            ));
        }

        return $errors->get($code);
    }

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
            $this->postError('invalid');
            return;
        }

        if ($this->pattern !== null && !preg_match($this->pattern, $this->data)) {
            $this->postError('invalidPattern');
            return;
        }

        if ($this->type == self::TYPE_EMAIL && !StringTools::isEmailAddress($this->data)) {
            $this->postError('invalidEmail');
            return;

        } elseif ($this->type == self::TYPE_URL && !StringTools::isUrl($this->data)) {
            $this->postError('invalidUrl');
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
                $this->postError($code, array('%length%' => $length));
                return;
            }
        }
    }

    public function save()
    {
    }
}
