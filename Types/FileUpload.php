<?php
namespace Cyantree\Grout\Types;

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

    public static function fromPhpFileArray($data)
    {
        if(!$data || $data['error'] == 4){
            return null;
        }

        $f = new FileUpload();
        $f->name = $data['name'];
        $f->file = $data['tmp_name'];
        $f->size = $data['size'];
        $f->error = $data['error'];

        return $f;
    }
}