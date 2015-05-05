<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\FileContentRenderer;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Types\FileUpload;

class FileContent extends Content
{
    public $required = false;

    public $maxFilesize = 0;

    public $saveDirectory;
    public $saveDirectoryUrl;
    public $saveFilename;

    public $keepExtension = false;

    /** @var FileUpload */
    public $uploadedFile;

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                    'required' => _('Im Feld „%name%“ wurde keine Datei ausgewählt.'),
                    'filesize' => _('Die Datei „%name%“ darf nicht größer als %size% MB sein.')
            ));
        }

        return $errors->get($code);
    }

    public function getFileUrl()
    {
        return $this->getFileUrlByValue($this->value);
    }

    public function getFilePath()
    {
        return $this->getFilePathByValue($this->value);
    }

    public function getFilePathByValue($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->saveDirectory === null) {
            throw new \Exception('saveDirectory has to be specified.');
        }

        return $this->saveDirectory . $value;
    }

    public function getFileUrlByValue($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->saveDirectoryUrl === null) {
            throw new \Exception('saveDirectoryUrl has to be specified.');
        }

        return $this->saveDirectoryUrl . $this->value;
    }

    public function reset()
    {
        parent::reset();

        $this->uploadedFile = null;
    }

    public function populate($data, $files)
    {
        if ($files->has($this->name)) {
            $file = $files->get($this->name);

            if (is_array($file)) {
                $file = null;
            }

            $this->uploadedFile = $file;
        }
    }

    public function check()
    {
        if (!$this->value && !$this->uploadedFile && $this->required) {
            $this->postError('required');
            return;
        }

        if ($this->uploadedFile) {
            if ($this->maxFilesize && $this->uploadedFile->size > $this->maxFilesize) {
                $filesize = round($this->maxFilesize / 1024 / 1024 * 10) / 10;
                $this->postError('filesize', array('%size%' => $filesize));
            }
        }
    }

    public function save()
    {
        if (!$this->uploadedFile) {
            return;
        }

        $oldValue = $this->value;
        $this->value = $this->generateValue();

        $this->saveFile();

        if ($this->value != $oldValue) {
            $this->onValueChanged($oldValue);
        }

        $this->uploadedFile->delete();
    }

    protected function generateValue()
    {
        if ($this->saveFilename) {
            return $this->saveFilename;

        } else {
            if ($this->keepExtension) {
                $extension = pathinfo($this->uploadedFile->name, PATHINFO_EXTENSION);

                if ($extension) {
                    $extension = '.' . $extension;
                }

            } else {
                $extension = '';
            }

            $value = FileTools::createUniqueFilename(
                    $this->saveDirectory,
                    $extension,
                    32,
                    true);

            if ($this->keepExtension) {
                $value .= $extension;
            }

            return $value;
        }
    }

    protected function saveFile()
    {
        $path = $this->getFilePathByValue($this->value);

        $this->uploadedFile->move($path);
    }

    protected function processFile()
    {

    }

    public function onDelete()
    {
        if ($this->value) {
            unlink($this->getFilePathByValue($this->value));
        }
    }

    protected function onValueChanged($oldValue)
    {
        if ($oldValue && $oldValue != $this->value) {
            unlink($this->getFilePathByValue($oldValue));
        }
    }
}
