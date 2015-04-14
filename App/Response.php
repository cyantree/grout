<?php
namespace Cyantree\Grout\App;

use Cyantree\Grout\App\Types\ContentType;
use Cyantree\Grout\App\Types\ResponseCode;

class Response
{
    public $code = ResponseCode::CODE_200;

    public $content;
    public $contentType = ContentType::TYPE_HTML_UTF8;
    public $contentLength;

    public $headers = array();
    private $headersSent = false;

    public function postHeaders()
    {
        if ($this->headersSent) {
            return;
        }

        $this->headersSent = true;

        header('Content-Type: ' . $this->contentType, null, intval($this->code));
        if ($this->contentLength) {
            header('Content-Length: ' . $this->contentLength);
        }

        foreach ($this->headers as $header => $value) {
            header($header . ': ' . $value);
        }
    }

    public function ignoreHeaders()
    {
        $this->headersSent = true;
    }

    public function postContent($content, $contentType = null, $overwriteExistingContent = false)
    {
        if ($this->content && !$overwriteExistingContent) {
            return false;
        }

        $this->content = $content;
        if ($contentType !== null) {
            $this->contentType = $contentType;
        }
        $this->contentLength = strlen($this->content);

        return true;
    }

    public function passthroughFile($file, $contentType = null)
    {
        if ($contentType !== null) {
            $this->contentType = $contentType;

        } else {
            $this->contentType = self::getContentTypeByFilename($file);
        }

        $this->content = null;
        $this->contentLength = filesize($file);

        $this->postHeaders();

        $f = fopen($file, 'r');
        fpassthru($f);
        fclose($f);
    }

    public function postFile($file, $contentType = null)
    {
        if (!is_file($file)) {
            return false;
        }

        if (!$contentType) {
            $contentType = self::getContentTypeByFilename($file);
        }

        $this->postContent(file_get_contents($file), $contentType, true);

        return true;
    }

    public function redirect($url, $responseCode = ResponseCode::CODE_301)
    {
        $this->code = $responseCode;
        $this->headers['Location'] = $url;
    }

    public function asDownload($filename)
    {
        if ($filename === null && isset($this->headers['Content-Disposition'])) {
            unset($this->headers['Content-Disposition']);
            $this->contentType = ContentType::TYPE_PLAIN_UTF8;

        } else {
            $this->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
            $this->contentType = ContentType::TYPE_BINARY;
        }
    }

    public static function getContentTypeByFilename($file, $defaultContentType = ContentType::TYPE_BINARY)
    {
        $fileType = strtolower(substr($file, strrpos($file, '.') + 1));

        if ($fileType == 'gif') {
            return ContentType::TYPE_GIF;

        } elseif ($fileType == 'jpg' || $fileType == 'jpeg') {
            return ContentType::TYPE_JPEG;

        } elseif ($fileType == 'png') {
            return ContentType::TYPE_PNG;

        } elseif ($fileType == 'js') {
            return ContentType::TYPE_JAVASCRIPT;

        } elseif ($fileType == 'css') {
            return ContentType::TYPE_CSS;

        } elseif ($fileType == 'html' || $fileType == 'htm') {
            return ContentType::TYPE_HTML;

        } elseif ($fileType == 'xml') {
            return ContentType::TYPE_XML;

        } elseif ($fileType == 'txt') {
            return ContentType::TYPE_PLAIN;

        } else {
            return $defaultContentType;
        }
    }
}
