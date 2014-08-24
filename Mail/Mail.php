<?php
namespace Cyantree\Grout\Mail;

use Cyantree\Grout\Tools\MailTools;

class Mail
{
    public static $defaultFrom;

    public $subject;
    public $text;
    public $htmlText;

    public $recipients = array();
    public $recipientsCc;
    public $recipientsBcc;

    /** @var string|array */
    public $from;

    public $returnPath;

    public function __construct($recipients = null, $subject = null, $text = null, $htmlText = null, $from = null)
    {
        $this->recipients = $recipients;
        $this->subject = $subject;
        $this->text = $text;
        $this->htmlText = $htmlText;
        $this->from = $from;
    }

    public function send()
    {
        $lineFeed = "\r\n";

        if (!is_array($this->recipients)) {
            $this->recipients = array($this->recipients);
        }

        if (!$this->from) $this->from = self::$defaultFrom;

        // Encode sender
        $from = is_array($this->from) ? MailTools::encodeString(current($this->from)) . ' <' . key($this->from) . '>' : $this->from;
        $headers = 'From: ' . str_replace(array(chr(13), chr(10)), array('', ''), $from);

        // Encode recipients
        $recipients = array();
        foreach ($this->recipients as $recipientMail => $recipientName) {
            if (is_string($recipientMail)) {
                $recipientMail = str_replace(array(chr(13), chr(10)), array('', ''), $recipientMail);
                $recipients[] = MailTools::encodeString($recipientName) . ' <' . $recipientMail . '>';
            } else $recipients[] = $recipientName;
        }

        // Encode recipients Cc
        if (is_array($this->recipientsCc)) {
            $recipientsTemp = array();
            foreach ($this->recipientsCc as $recipientMail => $recipientName) {
                if (is_string($recipientMail)) {
                    $recipientMail = str_replace(array(chr(13), chr(10)), array('', ''), $recipientMail);
                    $recipientsTemp[] = MailTools::encodeString($recipientName) . ' <' . $recipientMail . '>';
                } else $recipientsTemp[] = $recipientName;
            }
            $headers .= $lineFeed . 'CC: ' . implode(",\n ", $recipientsTemp);
        }

        // Encode recipients Bcc
        if (is_array($this->recipientsBcc)) {
            $recipientsTemp = array();
            foreach ($this->recipientsBcc as $recipientMail => $recipientName) {
                if (is_string($recipientMail)) {
                    $recipientMail = str_replace(array(chr(13), chr(10)), array('', ''), $recipientMail);
                    $recipientsTemp[] = MailTools::encodeString($recipientName) . ' <' . $recipientMail . '>';
                } else $recipientsTemp[] = $recipientName;
            }

            $headers .= $lineFeed . 'BCC: ' . implode(",\n ", $recipientsTemp);
        }

        // Encode subject
        $subject = MailTools::encodeString($this->subject);

        $headers .= $lineFeed . "MIME-Version: 1.0";

        // Create body
        if ($this->htmlText !== null && $this->htmlText !== '') {
            $body = '';

            $boundary = 'bd_' . md5(mt_rand() . time());

            $headers .= $lineFeed . 'Content-Type: multipart/alternative;' . "\n\t" . 'boundary="' . $boundary . '"';

            $body .= $lineFeed . $lineFeed . "--{$boundary}" . $lineFeed;
            $body .= 'Content-Type: text/plain; charset=utf-8' . $lineFeed;
            $body .= 'Content-Transfer-Encoding: quoted-printable' . $lineFeed . $lineFeed;
            $body .= quoted_printable_encode(str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $this->text));

            $body .= $lineFeed . $lineFeed . "--{$boundary}" . $lineFeed;
            $body .= 'Content-Type: text/html; charset=utf-8' . $lineFeed;
            $body .= 'Content-Transfer-Encoding: quoted-printable' . $lineFeed . $lineFeed;
            $body .= quoted_printable_encode(str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $this->htmlText));
            $body .= $lineFeed . $lineFeed . "--{$boundary}--" . $lineFeed . $lineFeed . $lineFeed;

        } else {
            $headers .= $lineFeed . 'Content-Type: text/plain; charset=utf-8' .
                  $lineFeed . 'Content-Transfer-Encoding: quoted-printable';
            $body = quoted_printable_encode(str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $this->text));
        }

        if($this->returnPath){
            $additionalParameters = '-f'.$this->returnPath;
        }else{
            $additionalParameters = null;
        }

        mail(implode(",\r\n ", $recipients), $subject, $body, $headers, $additionalParameters);
    }
}