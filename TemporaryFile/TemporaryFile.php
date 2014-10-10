<?php
namespace Cyantree\Grout\TemporaryFile;

class TemporaryFile
{
    public $id;
    public $path;
    public $expires;
    public $originalFilename;
    public $metadata;
    public $url;

    public $storeMetadata;

    public function setContent($content)
    {
        file_put_contents($this->path, $content);

        $this->save();
    }

    public function delete()
    {
        if (!$this->path) {
            return;
        }

        unlink($this->path);

        if ($this->storeMetadata) {
            unlink($this->path . '.dat');
        }

        $this->id = $this->path = $this->originalFilename = '';
        $this->metadata = null;
        $this->expires = 0;
    }

    public function replaceWithFile($file, $leaveFile = true)
    {
        if (!$this->path) {
            return;
        }

        unlink($this->path);

        $this->originalFilename = pathinfo($file, PATHINFO_BASENAME);

        if ($leaveFile) {
            copy($file, $this->path);

        } else {
            rename($file, $this->path);
        }

        $this->save();
    }

    public function save()
    {
        file_put_contents(
            $this->path . '.dat',
            serialize(array('filename' => $this->originalFilename, 'metadata' => $this->metadata))
        );

        if ($this->expires) {
            touch($this->path, $this->expires);
        }
    }

    public function _readAdditionalData()
    {
        $data = unserialize(file_get_contents($this->path . '.dat'));

        $this->originalFilename = $data['filename'];
        $this->metadata = $data['metadata'];
    }
}
