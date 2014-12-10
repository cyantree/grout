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
                    'notSelected' => _('Im Feld „%name%“ wurde keine Datei ausgewählt.'),
                    'invalidFilesize' => _('Die Datei „%name%“ darf nicht größer als %size% MB sein.')
            ));
        }

        return $errors->get($code);
    }

    public function getFileUrl()
    {
        return $this->saveDirectoryUrl . $this->value;
    }

    public function populate($data, $files)
    {
        if ($files->has($this->name)) {
            $this->uploadedFile = $files->get($this->name);
        }
    }

    public function check()
    {
        if (!$this->value && !$this->uploadedFile && $this->required) {
            $this->postError('notSelected');
            return;
        }

        if ($this->uploadedFile) {
            if ($this->maxFilesize && $this->uploadedFile->size > $this->maxFilesize) {
                $filesize = round($this->maxFilesize / 1024 / 1024 * 10) / 10;
                $this->postError('invalidFilesize', array('%size%' => $filesize));
            }
        }
    }

    public function save()
    {
        if ($this->value) {
            unlink($this->saveDirectory . $this->value);
        }

        if ($this->saveFilename) {
            $this->value = $this->saveFilename;
        } else {
            if ($this->keepExtension) {
                $extension = explode('.', $this->uploadedFile->name);
                $extension = '.' . strtolower(array_pop($extension));
            } else {
                $extension = '.dat';
            }

            $this->value = FileTools::createUniqueFilename($this->saveDirectory, $extension, 32, true) . $extension;
        }

        $this->uploadedFile->move($this->saveDirectory . $this->value);
    }

    public function onDelete()
    {
        if ($this->value) {
            unlink($this->saveDirectory . $this->value);
        }
    }
}
