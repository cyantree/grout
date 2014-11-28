<?php
namespace Cyantree\Grout\Status;

use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Translation\Translator;

class StatusContainer
{
    /** @var Translator */
    public $translator;
    public $translatorContext;

    public $escapingContext = Status::TYPE_PLAIN;

    /** @var Status[] */
    private $statuses = array();

    public $hasStatuses = false;

    /** @return Status */
    public function get($code)
    {
        if (isset($this->statuses[$code])) {
            return $this->statuses[$code];

        } else {
            return null;
        }
    }

    /** @return Status[] */
    public function getAll()
    {
        return $this->statuses;
    }

    /** @return Status */
    public function add(Status $status)
    {
        if (!$status->escapingContext) {
            $status->escapingContext = $this->escapingContext;
        }

        if (!$status->translatorContext) {
            $status->translatorContext = $this->translatorContext;
        }

        if ($status->code) {
            $this->statuses[$status->code] = $status;

        } else {
            $this->statuses[] = $status;
        }

        $this->hasStatuses = true;

        return $status;
    }

    /** @return Status */
    public function addManual($code, $message = null, $replaces = null, $escapingContext = null)
    {
        $status = new Status($code, $message, $replaces, $escapingContext);

        $this->add($status);

        return $status;
    }

    public function has($code)
    {
        return isset($this->statuses[$code]);
    }

    public function reset()
    {
        $this->statuses = array();
        $this->hasStatuses = false;
    }

    public function getMessage($codeOrStatus, $outputContext)
    {
        if (is_string($codeOrStatus)) {
            $codeOrStatus = $this->get($codeOrStatus);
        }

        /** @var $codeOrStatus Status */

        if (!$codeOrStatus) {
            return null;
        }

        $message = $codeOrStatus->message;

        if (!$message) {
            return null;
        }

        if ($this->translator) {
            $message = $this->translator->translate($message, $codeOrStatus->translatorContext);
        }

        if ($codeOrStatus->replaces) {
            $message = str_replace(array_keys($codeOrStatus->replaces), array_values($codeOrStatus->replaces), $message);
        }

        if ($codeOrStatus->escapingContext == Status::TYPE_PLAIN && $outputContext == Status::TYPE_HTML) {
            $message = StringTools::escapeHtml($message);

        } elseif ($codeOrStatus->escapingContext == Status::TYPE_HTML && $outputContext == Status::TYPE_PLAIN) {
            $message = html_entity_decode(strip_tags($message));
        }

        return $message;
    }

    public function getMessages($outputContext)
    {
        $messages = array();

        foreach ($this->statuses as $status) {
            $message = $this->getMessage($status, $outputContext);
            
            if (!$message) {
                continue;
            }

            if ($status->code) {
                $messages[$status->code] = $message;

            } else {
                $messages[] = $message;
            }
        }

        return $messages;
    }

    public function getCodes()
    {
        $codes = array();

        foreach ($this->statuses as $status) {
            if ($status->code) {
                $codes[] = $status->code;
            }
        }

        return $codes;
    }
}
