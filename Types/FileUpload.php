<?php
namespace Cyantree\Grout\Types;

use Cyantree\Grout\Tools\ArrayTools;

class FileUpload
{
    public $name;
    public $file;
    public $size;
    public $error;

    public function toPhpFileArray()
    {
        return array(
            'name' => $this->name,
            'tmp_name' => $this->file,
            'size' => $this->size,
            'error' => $this->error
        );
    }

    public function move($target, $copyIfNotUploaded = true)
    {
        if (!is_file($this->file)) {
            return false;
        }

        if (is_uploaded_file($this->file)) {
            move_uploaded_file($this->file, $target);

        } else {
            if ($copyIfNotUploaded) {
                copy($this->file, $target);

            } else {
                rename($this->file, $target);
            }
        }

        return true;
    }

    public function delete($keepIfNotUploaded = true)
    {
        if (!is_file($this->file)) {
            return false;
        }

        if (!$keepIfNotUploaded || is_uploaded_file($this->file)) {
            unlink($this->file);

            return true;
        }

        return false;
    }

    public static function fromPhpFileArray($data)
    {
        if (!$data || $data['error'] == 4) {
            return null;
        }

        $f = new FileUpload();
        $f->name = $data['name'];
        $f->file = $data['tmp_name'];
        $f->size = $data['size'];
        $f->error = $data['error'];

        return $f;
    }

    public static function fromFile($path, $name = null)
    {
        $f = new FileUpload();
        $f->name = $name ? $name : basename($path);
        $f->file = realpath($path);
        $f->size = filesize($path);
        $f->error = 0;

        return $f;
    }

    public static function fromMultiplePhpFileUploads($data)
    {
        $result = array();

        foreach ($data as $name => $upload) {
            if (!isset($upload['tmp_name'])) {
                continue;
            }

            if (is_array($upload['tmp_name'])) {
                $files = array();

                $upload = ArrayTools::unpack($upload);

                foreach ($upload as $uploadFile) {
                    $file = self::fromPhpFileArray($uploadFile);

                    if ($file) {
                        $files[] = $file;
                    }
                }

                if ($files) {
                    $result[$name] = $files;
                }

            } else {
                $file = self::fromPhpFileArray($upload);

                if ($file) {
                    $result[$name] = $file;
                }
            }
        }

        return $result;
    }
}
