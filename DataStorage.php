<?php
namespace Cyantree\Grout;

use Cyantree\Grout\Tools\FileTools;

class DataStorage
{
    public $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    public function getStorage($id)
    {
        $id = rtrim(str_replace('\\', '/', $id), '/');
        $path = $this->directory . $id . '/';

        return $path;
    }


    public function createStorage($id)
    {
        $id = rtrim(str_replace('\\', '/', $id), '/');
        $path = $this->directory . $id . '/';
        FileTools::createDirectory($path);

        return $path;
    }

    public function clearStorage($id)
    {
        $id = rtrim(str_replace('\\', '/', $id), '/');
        FileTools::deleteContents($this->directory . $id . '/');
    }

    public function clearAllStorages()
    {
        $storages = glob($this->directory . '*');

        if ($storages) {
            foreach ($storages as $storage) {
                if (is_dir($storage)) {
                    FileTools::deleteContents($storage);
                }
            }
        }
    }

    public function deleteStorage($id)
    {
        $id = rtrim(str_replace('\\', '/', $id), '/');
        FileTools::deleteDirectory($this->directory . $id);
    }

    public function deleteAllStorages()
    {
        $storages = glob($this->directory . '*');

        if ($storages) {
            foreach ($storages as $storage) {
                if (is_dir($storage)) {
                    FileTools::deleteDirectory($storage);
                }
            }
        }
    }
}
