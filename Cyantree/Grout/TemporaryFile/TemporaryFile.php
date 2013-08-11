<?php
namespace Cyantree\Grout\TemporaryFile;

class TemporaryFile
{
    public $id;
    public $path;
    public $expires;
    public $originalFilename;
    public $url;

    public function setContent($content)
    {
        file_put_contents($this->path, $content);
        touch($this->expires);
    }

    public function delete()
    {
        if (!$this->path) {
            return;
        }

        unlink($this->path);
        $this->id = $this->path = $this->originalFilename = '';
        $this->expires = 0;
    }

    public function replaceWithFile($file, $leaveFile = false)
    {
        if (!$this->path) {
            return;
        }

        unlink($this->path);

        if ($leaveFile) {
            copy($file, $this->path);
        } else {
            rename($file, $this->path);
        }
        touch($this->path, $this->expires);
    }

    public function updateExpirationDate($date = null)
    {
        if ($date != null) {
            $this->expires = $date;
        }

        touch($this->path, $this->expires);
    }
}