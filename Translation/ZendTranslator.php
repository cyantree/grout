<?php
namespace Cyantree\Grout\Translation;

use Zend\I18n\Translator as ZendTranslation;

class ZendTranslator extends Translator
{
    /** @var ZendTranslation\Translator */
    public $translator;

    public function __construct(ZendTranslation\Translator $translator)
    {
        $this->translator = $translator;
    }

    public function translate($message, $textDomain = null, $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }
}
