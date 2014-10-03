<?php
namespace Cyantree\Grout\Translation;

abstract class Translator
{
    abstract public function translate($message, $textDomain = null, $locale = null);
}