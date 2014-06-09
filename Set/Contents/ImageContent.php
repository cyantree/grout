<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\Set;
use Cyantree\Grout\Tools\ArrayTools;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Grout\Tools\StringTools;
use Cyantree\Grout\Types\FileUpload;

// Fake calls to enable gettext extraction
if (0) {
    _('Im Feld „%name%“ wurde kein Bild ausgewählt.');
    _('Im Feld „%name%“ wurde kein gültiges Bild ausgewählt.');
    _('Das Bild „%name%“ darf nicht größer als %size% MB sein.');
    _('Das Bild „%name%“ muss mindestens %width%x%height% Pixel groß sein.');
    _('Das Bild „%name%“ darf nicht größer als %width%x%height% Pixel sein.');
}

class ImageContent extends Content
{
    public $required = false;

    public $maxFilesize = 0;
    public $minWidth = 0;
    public $minHeight = 0;
    public $maxWidth = 0;
    public $maxHeight = 0;

    public $saveDirectory;
    public $saveDirectoryUrl;
    public $saveFilename;
    public $resizeToWidth;
    public $resizeToHeight;
    public $resizeImageToolsScaleMode = ImageTools::MODE_SCALE_CROP;
    public $resizeImageToolsBackground = 0x00000000;

    public $saveFormat = 'jpg';
    public $saveQuality = 90;

    public $valueContainsExtension = true;

    /** @var FileUpload */
    public $uploadedFile;

    protected $_image;

    public static $errorCodes = array(
        'notSelected' => 'Im Feld „%name%“ wurde kein Bild ausgewählt.',
        'invalidImage' => 'Im Feld „%name%“ wurde kein gültiges Bild ausgewählt.',
        'invalidFilesize' => 'Das Bild „%name%“ darf nicht größer als %size% MB sein.',
        'tooSmall' => 'Das Bild „%name%“ muss mindestens %width%x%height% Pixel groß sein.',
        'tooLarge' => 'Das Bild „%name%“ darf nicht größer als %width%x%height% Pixel sein.'
    );

    public function render($mode)
    {
        if ($mode == Set::MODE_DELETE || $mode == Set::MODE_LIST) {
            if ($this->_data) {
                return '<img id="' . $this->name . '_preview" src="' .
                StringTools::escapeHtml($this->_getImageUrl()) . '" alt="" />';
            }

            return '';
        }

        $c = '<input type="file" name="' . $this->name . '" />';
        if ($this->_data) {
            $c .= '<br /><br /><img src="' . StringTools::escapeHtml($this->_getImageUrl()) . '" alt="" />';
        }

        return $c;
    }

    protected function _getImageUrl()
    {
        return $this->saveDirectoryUrl . $this->_data . ($this->valueContainsExtension ? '' : '.' . $this->saveFormat);
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
                return;
            }
            $image = ImageTools::checkFile($this->uploadedFile->file, $this->uploadedFile->name);
            if (!$image->success) {
                $this->postError('invalidImage', self::$errorCodes['invalidImage']);
                return;
            }

            if ($this->minWidth || $this->maxWidth) {
                $sizeX = imagesx($image->image);
                $sizeY = imagesy($image->image);

                if ($this->minWidth && ($sizeX < $this->minWidth || $sizeY < $this->minHeight)) {
                    $this->postError('tooSmall', self::$errorCodes['tooSmall'], array('%width%' => $this->minWidth, '%height%' => $this->minHeight));
                } elseif ($this->maxWidth && ($sizeX > $this->maxWidth || $sizeY > $this->maxHeight)) {
                    $this->postError('tooLarge', self::$errorCodes['tooLarge'], array('%width%' => $this->maxWidth, '%height%' => $this->maxHeight));
                }
            }

            $this->_image = $image->image;
        }
    }


    public function save()
    {
        if ($this->_image) {
            $this->onProcessImage($this->_image);

            if ($this->_data) {
                $this->saveFilename = $this->_data;
                if (!$this->valueContainsExtension) {
                    $this->saveFilename .= '.' . $this->saveFormat;
                }
            } else if (!$this->saveFilename) {
                $this->_data = FileTools::createUniqueFilename($this->saveDirectory, '.' . $this->saveFormat, 32, true);

                if ($this->valueContainsExtension)
                    $this->saveFilename = $this->_data = $this->_data . '.' . $this->saveFormat;
                else
                    $this->saveFilename = $this->_data . '.' . $this->saveFormat;
            }

            if ($this->resizeToWidth) {
                $image = ImageTools::resizeImage($this->_image, $this->resizeToWidth, $this->resizeToHeight, false, $this->resizeImageToolsScaleMode, $this->resizeImageToolsBackground);
            } else $image = $this->_image;


            if ($this->saveFormat == 'jpg') {
                imagejpeg($image, $this->saveDirectory . $this->saveFilename, $this->saveQuality);
            } else if ($this->saveFormat == 'png') {
                imagepng($image, $this->saveDirectory . $this->saveFilename);
            }

            $this->onImageProcessed($this->_image);

            if ($image != $this->_image) imagedestroy($this->_image);
            imagedestroy($image);
        }
    }

    public function onProcessImage($image)
    {

    }

    public function onImageProcessed($image)
    {
    }

    public function onDelete()
    {
        if ($this->_data) unlink($this->saveDirectory . $this->_data . ($this->valueContainsExtension ? '' : '.' . $this->saveFormat));
    }
}