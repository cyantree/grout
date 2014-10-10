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
        $url = $this->_getImageUrl();

        if ($mode == Set::MODE_EXPORT) {
            return $url ? $url : $this->data;
        }

        if ($this->editable && ($mode == Set::MODE_ADD || $mode == Set::MODE_EDIT)) {
            $c = '<input type="file" name="' . $this->name . '" />';

        } else {
            $c = '';
        }

        if ($this->data) {
            if ($c) {
                $c .= '<br /><br />';
            }

            if ($url) {
                $c .= '<img id="' . $this->name . '_preview" src="' .
                      StringTools::escapeHtml($url) . '" alt="" />';

            } else {
                $c .= StringTools::escapeHtml($this->data);
            }
        }

        return $c;
    }

    protected function _getImageUrl()
    {
        return $this->saveDirectoryUrl . $this->data . ($this->valueContainsExtension ? '' : '.' . $this->saveFormat);
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
                    $this->postError(
                        'tooSmall',
                        self::$errorCodes['tooSmall'],
                        array('%width%' => $this->minWidth, '%height%' => $this->minHeight)
                    );

                } elseif ($this->maxWidth && ($sizeX > $this->maxWidth || $sizeY > $this->maxHeight)) {
                    $this->postError(
                        'tooLarge',
                        self::$errorCodes['tooLarge'],
                        array('%width%' => $this->maxWidth, '%height%' => $this->maxHeight)
                    );
                }
            }

            $this->_image = $image->image;
        }
    }


    public function save()
    {
        if ($this->_image) {
            $this->onProcessImage($this->_image);

            $saveFilename = null;
            $oldSaveFilename = $this->data;

            if ($oldSaveFilename && !$this->valueContainsExtension) {
                $oldSaveFilename .= '.' . $this->saveFormat;
            }

            if ($this->saveFilename) {
                $this->data = $saveFilename = $this->saveFilename;

            } elseif (!$this->saveFilename) {
                $this->data = FileTools::createUniqueFilename($this->saveDirectory, '.' . $this->saveFormat, 32, true);

                if ($this->valueContainsExtension) {
                    $saveFilename = $this->data = $this->data . '.' . $this->saveFormat;

                } else {
                    $saveFilename = $this->data . '.' . $this->saveFormat;
                }
            }

            if ($this->resizeToWidth) {
                $image = ImageTools::resizeImage(
                    $this->_image,
                    $this->resizeToWidth,
                    $this->resizeToHeight,
                    false,
                    $this->resizeImageToolsScaleMode,
                    $this->resizeImageToolsBackground
                );

            } else {
                $image = $this->_image;
            }

            if ($oldSaveFilename && $oldSaveFilename != $saveFilename) {
                unlink($this->saveDirectory . $oldSaveFilename);
            }

            if ($this->saveFormat == 'jpg') {
                imagejpeg($image, $this->saveDirectory . $saveFilename, $this->saveQuality);

            } elseif ($this->saveFormat == 'png') {
                imagepng($image, $this->saveDirectory . $saveFilename);
            }

            $this->onImageProcessed($this->_image);

            if ($image != $this->_image) {
                imagedestroy($this->_image);
            }
            imagedestroy($image);

            $this->uploadedFile->delete();
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
        if ($this->data) {
            unlink($this->saveDirectory . $this->data . ($this->valueContainsExtension ? '' : '.' . $this->saveFormat));
        }
    }
}
