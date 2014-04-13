<?php
namespace Cyantree\Grout\Translation;

class ZendTranslator extends Translator
{
    /** @var \Zend\I18n\Translator\Translator */
    public $translator;

    public function __construct(\Zend\I18n\Translator\Translator $translator)
    {
        $this->translator = $translator;
    }

    public function translate($message, $textDomain = null, $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }
}