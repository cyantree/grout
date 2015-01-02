<?php
namespace Cyantree\Grout\Mail;

use Cyantree\Grout\Tools\MailTools;

class Mail
{
    public static $defaultFrom;
    public static $headerLineFeed = "\r\n";
    public static $bodyLineFeed = "\r\n";

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
        $headerLineFeed = self::$headerLineFeed;

        if (!$this->from) {
            $this->from = self::$defaultFrom;
        }

        // Encode sender
        $headers = 'From: ' . implode(",{$headerLineFeed} ", $this->encodeAddresses($this->from));

        // Encode recipients
        $recipients = $this->encodeAddresses($this->recipients);

        // Process CC
        if ($this->recipientsCc) {
            $headers .= $headerLineFeed . 'CC: ' . implode(",{$headerLineFeed} ", $this->encodeAddresses($this->recipientsCc));
        }

        // Process BCC
        if ($this->recipientsBcc) {
            $headers .= $headerLineFeed . 'BCC: ' . implode(",{$headerLineFeed} ", $this->encodeAddresses($this->recipientsBcc));
        }

        // Process Reply-To
        if ($this->replyTo) {
            $headers .= $headerLineFeed . 'Reply-To: ' . implode(",{$headerLineFeed} ", $this->encodeAddresses($this->replyTo));
        }

        // Process additional headers
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (is_string($name)) {
                    $headers .= $headerLineFeed . $name . ': ' . $value;

                } else {
                    $headers .= $headerLineFeed . $value;
                }
            }
        }

        // Encode subject
        $subject = MailTools::encodeString($this->subject);

        $headers .= $headerLineFeed . 'MIME-Version: 1.0';

        // Create body
        if ($this->htmlText !== null && $this->htmlText !== '') {
            $body = '';

            $boundary = 'bd_' . md5(mt_rand() . time());

            $headers .= $headerLineFeed . 'Content-Type: multipart/alternative;' . $headerLineFeed . "\t" . 'boundary="' . $boundary . '"';

            $body .= $headerLineFeed . $headerLineFeed . "--{$boundary}" . $headerLineFeed;
            $body .= 'Content-Type: text/plain; charset=utf-8' . $headerLineFeed;
            $body .= 'Content-Transfer-Encoding: quoted-printable' . $headerLineFeed . $headerLineFeed;
            $body .= $this->encodeText($this->text);

            $body .= $headerLineFeed . $headerLineFeed . "--{$boundary}" . $headerLineFeed;
            $body .= 'Content-Type: text/html; charset=utf-8' . $headerLineFeed;
            $body .= 'Content-Transfer-Encoding: quoted-printable' . $headerLineFeed . $headerLineFeed;
            $body .= $this->encodeText($this->htmlText);
            $body .= $headerLineFeed . $headerLineFeed . "--{$boundary}--" . $headerLineFeed . $headerLineFeed . $headerLineFeed;

        } else {
            $headers .= $headerLineFeed . 'Content-Type: text/plain; charset=utf-8' .
                    $headerLineFeed . 'Content-Transfer-Encoding: quoted-printable';
            $body = $this->encodeText($this->text);
        }

        if ($this->returnPath) {
            $additionalParameters = '-f' . $this->returnPath;

        } else {
            $additionalParameters = null;
        }

        mail(implode(",{$headerLineFeed} ", $recipients), $subject, $body, $headers, $additionalParameters);
    }

    private function encodeText($text)
    {
        if (self::$bodyLineFeed !== null) {
            $text = str_replace("\r", "", $text);

            if (self::$bodyLineFeed !== "\n") {
                $text = str_replace("\n", self::$bodyLineFeed, $text);
            }
        }

        return quoted_printable_encode($text);
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
