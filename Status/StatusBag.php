<?php
namespace Cyantree\Grout\Status;

use Cyantree\Grout\Translation\Translator;

class StatusBag
{
    /** @var StatusContainer */
    public $success;

    /** @var StatusContainer */
    public $info;

    /** @var StatusContainer */
    public $warning;

    /** @var StatusContainer */
    public $error;

    public function __construct()
    {
        $this->success = new StatusContainer();
        $this->info = new StatusContainer();
        $this->warning = new StatusContainer();
        $this->error = new StatusContainer();
    }

    public function reset()
    {
        $this->success->reset();
        $this->info->reset();
        $this->warning->reset();
        $this->error->reset();
    }

    public function setTranslator(Translator $translator, $context = null)
    {
        $this->success->translator = $this->error->translator =
            $this->warning->translator = $this->info->translator = $translator;

        if ($context) {
            $this->success->translatorContext = $this->error->translatorContext =
                $this->warning->translatorContext = $this->info->translatorContext = $context;
        }
    }
}
