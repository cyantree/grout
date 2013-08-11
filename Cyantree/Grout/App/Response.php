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

    public function postHeaders()
    {
        header('HTTP/1.1 ' . $this->code);
        header('Content-Type: ' . $this->contentType);
        if ($this->contentLength) {
            header('Content-Length: ' . $this->contentLength);
        }

        foreach ($this->headers as $header => $value) header($header . ': ' . $value);
    }

    public function postContent($content, $contentType = null, $overwriteExistingContent = false)
    {
        if ($this->content && !$overwriteExistingContent){
            return false;
        }

        $this->content = $content;
        if ($contentType !== null){
            $this->contentType = $contentType;
        }
        $this->contentLength = strlen($this->content);

        return true;
    }

    public function postFile($file, $contentType = null)
    {
        if (!is_file($file))
            return false;

        if (!$contentType) {
            $fileType = strtolower(substr($file, strrpos($file, '.') + 1));

            if ($fileType == 'gif') $contentType = ContentType::TYPE_GIF;
            else if ($fileType == 'jpg' || $fileType == 'jpeg') $contentType = ContentType::TYPE_JPEG;
            else if ($fileType == 'png') $contentType = ContentType::TYPE_PNG;
            else if ($fileType == 'js') $contentType = ContentType::TYPE_JAVASCRIPT;
            else if ($fileType == 'css') $contentType = ContentType::TYPE_CSS;
            else if ($fileType == 'html' || $fileType == 'htm') $contentType = ContentType::TYPE_HTML;
            else if ($fileType == 'xml') $contentType = ContentType::TYPE_XML;
            else if ($fileType == 'txt') $contentType = ContentType::TYPE_PLAIN;
            else $contentType = ContentType::TYPE_BINARY;
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
}