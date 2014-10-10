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
        $url = $this->getFileUrl();

        if ($mode == Set::MODE_EXPORT) {
            return $url ? $url : $this->data;
        }

        if ($this->editable && ($mode == Set::MODE_ADD || $mode == Set::MODE_EDIT)) {
            $c = '<input type="file" name="' . $this->name . '" />';

        } else {
            $c = '';
        }

        if ($this->data) {
            if ($c != '') {
                $c .= '<br /><br />';
            }

            if ($url) {
                $c .= '<a href="' . StringTools::escapeHtml($url) . '" target="_blank">'
                    . StringTools::escapeHtml($url) . '</a>';

            } else {
                $c .= StringTools::escapeHtml($this->data);
            }
        }

        return $c;
    }

    protected function getFileUrl()
    {
        return $this->saveDirectoryUrl . $this->data;
    }

    public function populate($data, $files)
    {
        $this->uploadedFile = $files->get($this->name);
    }

    public function check()
    {
        if (!$this->data && !$this->uploadedFile && $this->required) {
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
        if ($this->data) {
            unlink($this->saveDirectory . $this->data);
        }

        if ($this->saveFilename) {
            $this->data = $this->saveFilename;
        } else {
            if ($this->keepExtension) {
                $extension = explode('.', $this->uploadedFile->name);
                $extension = '.' . strtolower(array_pop($extension));
            } else {
                $extension = '.dat';
            }

            $this->data = FileTools::createUniqueFilename($this->saveDirectory, $extension, 32, true) . $extension;
        }

        $this->uploadedFile->move($this->saveDirectory . $this->data);
    }

    public function onDelete()
    {
        if ($this->data) {
            unlink($this->saveDirectory . $this->data);
        }
    }
}
