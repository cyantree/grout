<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
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

    public function render($mode)
    {
        $url = $this->_getFileUrl();

        if ($mode == Set::MODE_EXPORT) {
            return $url ? $url : $this->_data;
        }

        if ($this->editable && ($mode == Set::MODE_ADD || $mode == Set::MODE_EDIT)) {
            $c = '<input type="file" name="' . $this->name . '" />';

        } else {
            $c = '';
        }

        if ($this->_data) {
            if ($c != '') {
                $c .= '<br /><br />';
            }

            if ($url) {
                $c .= '<a href="' . StringTools::escapeHtml($url) . '" target="_blank">' . StringTools::escapeHtml($url) . '</a>';

            } else {
                $c .= StringTools::escapeHtml($this->_data);
            }
        }

        return $c;
    }

    protected function _getFileUrl()
    {
        return $this->saveDirectoryUrl . $this->_data;
    }

    public function populate($data)
    {
        $this->uploadedFile = ArrayTools::getPrepared($_FILES, $this->name, 'file');
    }

    public function check()
    {
        if (!$this->_data && !$this->uploadedFile && $this->required) {
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
        if ($this->_data) {
            unlink($this->saveDirectory . $this->_data);
        }

        if ($this->saveFilename) {
            $this->_data = $this->saveFilename;
        } else {
            if ($this->keepExtension) {
                $extension = explode('.', $this->uploadedFile->name);
                $extension = '.' . strtolower(array_pop($extension));
            } else {
                $extension = '.dat';
            }

            $this->_data = FileTools::createUniqueFilename($this->saveDirectory, $extension, 32, true) . $extension;
        }

        move_uploaded_file($this->uploadedFile->file, $this->saveDirectory . $this->_data);
    }

    public function onDelete()
    {
        if ($this->_data) {
            unlink($this->saveDirectory . $this->_data);
        }
    }
}