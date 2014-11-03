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
    public $replyTo;

    public $headers;

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

        if (!$this->from) {
            $this->from = self::$defaultFrom;
        }

        // Encode sender
        $from = is_array($this->from) ?
            MailTools::encodeString(current($this->from)) . ' <' . key($this->from) . '>' :
            $this->from;
        $headers = 'From: ' . str_replace(array(chr(13), chr(10)), array('', ''), $from);

        // Encode recipients
        $recipients = $this->encodeAddresses($this->recipients);

        // Process CC
        if ($this->recipientsCc) {
            $headers .= $lineFeed . 'CC: ' . implode(',' . $lineFeed, $this->encodeAddresses($this->recipientsCc));
        }

        // Process BCC
        if ($this->recipientsBcc) {
            $headers .= $lineFeed . 'BCC: ' . implode(',' . $lineFeed, $this->encodeAddresses($this->recipientsBcc));
        }

        // Process Reply-To
        if ($this->replyTo) {
            $headers .= $lineFeed . 'Reply-To: ' . implode(',' . $lineFeed, $this->encodeAddresses($this->replyTo));
        }

        // Process additional headers
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (is_string($name)) {
                    $headers .= $lineFeed . $name . ': ' . $value;

                } else {
                    $headers .= $lineFeed . $value;
                }
            }
        }

        // Encode subject
        $subject = MailTools::encodeString($this->subject);

        $headers .= $lineFeed . 'MIME-Version: 1.0';

        // Create body
        if ($this->htmlText !== null && $this->htmlText !== '') {
            $body = '';

            $boundary = 'bd_' . md5(mt_rand() . time());

            $headers .= $lineFeed . 'Content-Type: multipart/alternative;' . $lineFeed . "\t" . 'boundary="' . $boundary . '"';

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

        if ($this->returnPath) {
            $additionalParameters = '-f' . $this->returnPath;

        } else {
            $additionalParameters = null;
        }

        mail(implode(",\r\n ", $recipients), $subject, $body, $headers, $additionalParameters);
    }

    private function encodeAddresses($addresses)
    {
        if (!is_array($addresses)) {
            $addresses = array($addresses);
        }

        $newAddresses = array();

        foreach ($addresses as $mail => $name) {
            if (is_string($mail)) {
                $mail = str_replace(array(chr(13), chr(10)), array('', ''), $mail);
                $newAddresses[] = MailTools::encodeString($name) . ' <' . $mail . '>';

            } else {
                $newAddresses[] = $name;
            }
        }

        return $newAddresses;
    }
}
