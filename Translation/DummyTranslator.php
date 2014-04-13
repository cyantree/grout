<?php
namespace Cyantree\Grout\Translation;

class DummyTranslator extends Translator
{
    public function translate($message, $textDomain = null, $locale = null)
    {
        return $message;
    }
}