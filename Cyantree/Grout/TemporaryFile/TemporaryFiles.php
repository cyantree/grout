<?php
namespace Cyantree\Grout\TemporaryFile;

use Cyantree\Grout\Tools\StringTools;

class TemporaryFiles
{
    public $directory;
    public $baseUrl;
    public $extension = '.tmp';

    public $filenameChars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    public $filenamePrefix = '';
    public $filenameLength = 16;

    public function __construct($directory = null, $extension = '.tmp', $baseURL = null)
    {
        $this->directory = $directory;
        $this->extension = $extension;
        $this->baseUrl = $baseURL;
    }

    public function getContentById($id)
    {
        if (!preg_match('!^[a-zA-Z0-9_-]{1,100}$!', $id)) {
            return false;
        }

        $p = $this->directory . $id . $this->extension;
        if (!file_exists($p)) return false;

        return file_get_contents($p);
    }

    public function loadById($id)
    {
        if (!preg_match('!^[a-zA-Z0-9_-]{1,100}$!', $id)) return false;
        $path = $this->directory . $id . $this->extension;

        if (!file_exists($path)) return false;
        $expires = filemtime($path);
        if ($expires < time()) {
            unlink($path);
            return false;
        }

        $f = new TemporaryFile();
        $f->id = $id;
        $f->path = $path;
        $f->expires = $expires;
        $f->url = $this->baseUrl . $id . $this->extension;

        return $f;
    }

    public function createFromExistingFile($file, $expirationDate = 86400, $id = null, $leaveFile = false)
    {
        $t = time();

        if ($expirationDate < 1000000000) $expirationDate += time();

        $f = new TemporaryFile();
        if ($id !== null) {
            $f->id = $id;
            $f->path = $this->directory . $f->id . $this->extension;
        } else {
            do {
                $f->id = $this->filenamePrefix . StringTools::random($this->filenameLength, $this->filenameChars);
                $f->path = $this->directory . $f->id . $this->extension;
                $exists = is_file($f->path) && filemtime($f->path) > $t;
            } while ($id === null && $exists);
        }

        $f->expires = $expirationDate;
        $f->url = $this->baseUrl . $f->id . $this->extension;

        $f->originalFilename = pathinfo($file, PATHINFO_BASENAME);

        if ($leaveFile) copy($file, $f->path);
        else rename($file, $f->path);
        touch($f->path, $expirationDate);

        return $f;
    }

    public function createFromContent($content, $expirationDate = 86400, $id = null)
    {
        $t = time();

        if ($expirationDate < 1000000000) $expirationDate += $t;

        $f = new TemporaryFile();

        if ($id !== null) {
            $f->id = $id;
            $f->path = $this->directory . $f->id . $this->extension;
        } else {
            do {
                $f->id = $this->filenamePrefix . StringTools::random($this->filenameLength, $this->filenameChars);
                $f->path = $this->directory . $f->id . $this->extension;
                $exists = is_file($f->path) && filemtime($f->path) > $t;
            } while ($exists);
        }

        $f->expires = $expirationDate;
        $f->url = $this->baseUrl . $f->id . $this->extension;

        file_put_contents($f->path, $content);
        touch($f->path, $expirationDate);

        return $f;
    }

    public function cleanUp()
    {
        $files = glob($this->directory . $this->filenamePrefix . '*' . $this->extension, GLOB_NOSORT);

        $t = time();

        if ($files) {
            foreach ($files as $file) {
                if (filemtime($file) < $t)
                    unlink($file);
            }
        }
    }
}