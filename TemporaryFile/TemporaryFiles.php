<?php
namespace Cyantree\Grout\TemporaryFile;

use Cyantree\Grout\TemporaryFile\TemporaryFile;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\StringTools;

class TemporaryFiles
{
    public $directory;
    public $baseUrl;
    public $extension = '.tmp';

    public $idRegEx = '!^[a-zA-Z0-9_-]{1,100}$!';
    public $idChars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    public $idPrefix = '';
    public $idSuffix = '';
    public $idLength = 16;

    public $pathChunkSize = 0;
    public $pathMaxChunks = 0;


    public $filesExpire = true;
    public $storeMetadata = true;

    public function __construct($directory = null, $extension = '.tmp', $baseURL = null)
    {
        $this->directory = $directory;
        $this->extension = $extension;
        $this->baseUrl = $baseURL;
    }

    public function getPathById($id, $validateId = true)
    {
        if ($validateId) {
            if (!$this->isValidId($id)) {
                return null;
            }
        }

        if (!$this->pathChunkSize) {
            return $this->directory . $id . $this->extension;
        }

        if (!$this->pathMaxChunks) {
            return $this->directory . substr(chunk_split($id, $this->pathChunkSize, '/'), 0, -1) . $this->extension;

        } else {
            $len = $this->pathMaxChunks * $this->pathChunkSize;

            if (strlen($id) <= $len) {
                $id = substr(chunk_split(substr($id, 0, $len), $this->pathChunkSize, '/'), 0, -1);

            } else {
                $id = chunk_split(substr($id, 0, $len), $this->pathChunkSize, DIRECTORY_SEPARATOR) . substr($id, $len);
            }

            return $this->directory . $id . $this->extension;
        }
    }

    private function isValidId($id)
    {
        return preg_match($this->idRegEx, $id);
    }

    public function getContentById($id, $ignoreExpiration = false)
    {
        $path = $this->getPathById($id);

        if ($path === null) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        if (!$ignoreExpiration && $this->filesExpire) {
            $expires = filemtime($path);
            if ($expires < time()) {
                $this->deleteById($id);
                return false;
            }
        }

        return file_get_contents($path);
    }

    public function deleteById($id)
    {
        $path = $this->getPathById($id);

        if (file_exists($path)) {
            unlink($path);

            if ($this->storeMetadata) {
                unlink($path . '.dat');
            }
        }
    }

    public function loadById($id, $ignoreExpiration = false)
    {
        $path = $this->getPathById($id);

        if ($path === null) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        if ($this->filesExpire) {
            $expires = filemtime($path);
            if (!$ignoreExpiration && $expires < time()) {
                $this->deleteById($id);
                return false;
            }

        } else {
            $expires = 0;
        }

        $f = new TemporaryFile();
        $f->storeMetadata = $this->storeMetadata;

        $f->id = $id;
        $f->path = $path;
        $f->expires = $expires;
        $f->url = $this->baseUrl . $id . $this->extension;
        $f->readAdditionalData();

        return $f;
    }

    public function createFromExistingFile(
        $file,
        $expirationDate = 86400,
        $id = null,
        $leaveFile = true,
        $metadata = null,
        $originalFilename = null
    ) {
        if ($this->filesExpire) {
            $t = time();

            if ($expirationDate < 1000000000) {
                $expirationDate += time();
            }

        } else {
            $t = $expirationDate = 0;
        }

        $f = new TemporaryFile();
        $f->storeMetadata = $this->storeMetadata;

        if ($id !== null) {
            $f->id = $id;
            $f->path = $this->getPathById($id, false);

        } else {
            do {
                $f->id = $this->idPrefix . StringTools::random($this->idLength, $this->idChars) . $this->idSuffix;
                $f->path = $this->getPathById($f->id, false);
                $exists = is_file($f->path) && (!$this->filesExpire || filemtime($f->path) > $t);
            } while ($id === null && $exists);
        }

        $f->expires = $expirationDate;
        $f->url = $this->baseUrl . $f->id . $this->extension;

        $f->originalFilename = $originalFilename === null ? basename($file) : $originalFilename;
        $f->metadata = $metadata;

        if ($this->pathChunkSize) {
            $dir = dirname($f->path);

            if (!is_dir($dir)) {
                FileTools::createDirectory($dir);
            }
        }

        if ($leaveFile) {
            copy($file, $f->path);

        } else {
            rename($file, $f->path);
        }

        $f->save();

        return $f;
    }

    public function createFromContent(
        $content,
        $expirationDate = 86400,
        $id = null,
        $metadata = null,
        $originalFilename = null
    ) {
        if ($this->filesExpire) {
            $t = time();

            if ($expirationDate < 1000000000) {
                $expirationDate += $t;
            }

        } else {
            $t = $expirationDate = 0;
        }

        $f = new TemporaryFile();
        $f->storeMetadata = $this->storeMetadata;

        if ($id !== null) {
            $f->id = $id;
            $f->path = $this->getPathById($id, false);

        } else {
            do {
                $f->id = $this->idPrefix . StringTools::random($this->idLength, $this->idChars) . $this->idSuffix;
                $f->path = $this->getPathById($f->id, false);
                $exists = is_file($f->path) && (!$this->filesExpire || filemtime($f->path) > $t);
            } while ($exists);
        }

        $f->originalFilename = $originalFilename;
        $f->expires = $expirationDate;
        $f->url = $this->baseUrl . $f->id . $this->extension;
        $f->metadata = $metadata;

        if ($this->pathChunkSize) {
            $dir = dirname($f->path);

            if (!is_dir($dir)) {
                FileTools::createDirectory($dir);
            }
        }

        file_put_contents($f->path, $content);

        $f->save();

        return $f;
    }

    public function deleteAll()
    {
        FileTools::deleteContents($this->directory);
    }

    public function cleanUp()
    {
        if (!$this->filesExpire) {
            return;
        }

        $t = time();

        $directories = FileTools::listDirectory($this->directory, true, false);
        array_unshift($directories, '');

        foreach ($directories as $directory) {
            $files = glob(
                $this->directory . $directory . $this->idPrefix . '*' . $this->idSuffix . $this->extension,
                GLOB_NOSORT
            );

            if ($files) {
                foreach ($files as $file) {
                    if (filemtime($file) < $t) {
                        unlink($file);

                        if ($this->storeMetadata) {
                            unlink($file . '.dat');
                        }
                    }
                }
            }
        }
    }
}
