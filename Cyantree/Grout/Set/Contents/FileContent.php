<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Types\FileUpload;

// Fake calls to enable gettext extraction
if (0) {
    _('Das Feld „%name%“ wurde nicht ausgewählt.');
}

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

    protected $_v;

    public static $errorCodes;

    public function __construct()
    {
        if (!self::$errorCodes) {
            self::$errorCodes = array(
                'notSelected' => _('Im Feld „%name%“ wurde keine Datei ausgewählt.'),
                'invalidFilesize' => _('Die Datei „%name%“ darf nicht größer als %size% MB sein.')
            );
        }

        parent::__construct();
    }

    public function render($mode, $namespace = null)
    {
        $c = '<input type="file" name="' . $namespace . '" />';
        if ($this->_v) {
            $c .= '<br /><br />' . StringTools::escapeHtml($this->_getFileUrl());
        }

        return $c;
    }

    protected function _getFileUrl()
    {
        return $this->saveDirectoryUrl . $this->_v;
    }

    public function populate($data, $namespace)
    {
        $this->uploadedFile = ArrayTools::getPrepared($_FILES, $namespace, 'file');
    }

    public function check()
    {
        if (!$this->_v && !$this->uploadedFile && $this->required) {
            $this->postError('notSelected', self::$errorCodes['notSelected']);
            return;
        }

        if ($this->uploadedFile) {
            if ($this->maxFilesize && $this->uploadedFile->size > $this->maxFilesize) {
                $filesize = round($this->maxFilesize / 1024 / 1024 * 10) / 10;
                $this->postError('invalidFilesize', self::$errorCodes['invalidFilesize'], array('%size%' => $filesize));
            }
        }
    }


    public function save()
    {
        if ($this->_v) {
            unlink($this->saveDirectory . $this->_v);
        }

        if ($this->saveFilename) {
            $this->_v = $this->saveFilename;
        } else {
            if ($this->keepExtension) {
                $extension = explode('.', $this->uploadedFile->name);
                $extension = '.' . strtolower(array_pop($extension));
            } else {
                $extension = '.dat';
            }

            $this->_v = FileTools::createUniqueFilename($this->saveDirectory, $extension, 32, true) . $extension;
        }

        move_uploaded_file($this->uploadedFile->file, $this->saveDirectory . $this->_v);
    }

    public function encode()
    {
        return $this->_v;
    }

    public function decode($data)
    {
        $this->_v = $data;
    }

    public function onDelete()
    {
        if ($this->_v) {
            unlink($this->saveDirectory . $this->_v);
        }
    }
}