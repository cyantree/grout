<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRenderers\ImageContentRenderer;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Grout\Types\FileUpload;

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

    protected $image;

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                    'notSelected' => _('Im Feld „%name%“ wurde kein Bild ausgewählt.'),
                    'invalidImage' => _('Im Feld „%name%“ wurde kein gültiges Bild ausgewählt.'),
                    'invalidFilesize' => _('Das Bild „%name%“ darf nicht größer als %size% MB sein.'),
                    'tooSmall' => _('Das Bild „%name%“ muss mindestens %width%x%height% Pixel groß sein.'),
                    'tooLarge' => _('Das Bild „%name%“ darf nicht größer als %width%x%height% Pixel sein.')
            ));
        }

        return $errors->get($code);
    }

    public function getImageUrl()
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
            $this->postError('notSelected');
            return;
        }

        if ($this->uploadedFile) {
            if ($this->maxFilesize && $this->uploadedFile->size > $this->maxFilesize) {
                $filesize = round($this->maxFilesize / 1024 / 1024 * 10) / 10;

                $this->postError('invalidFilesize', array('%size%' => $filesize));
                return;
            }
            $image = ImageTools::checkFile($this->uploadedFile->file, $this->uploadedFile->name);
            if (!$image->success) {
                $this->postError('invalidImage');
                return;
            }

            if ($this->minWidth || $this->maxWidth) {
                $sizeX = imagesx($image->image);
                $sizeY = imagesy($image->image);

                if ($this->minWidth && ($sizeX < $this->minWidth || $sizeY < $this->minHeight)) {
                    $this->postError(
                        'tooSmall',
                        array('%width%' => $this->minWidth, '%height%' => $this->minHeight)
                    );

                } elseif ($this->maxWidth && ($sizeX > $this->maxWidth || $sizeY > $this->maxHeight)) {
                    $this->postError(
                        'tooLarge',
                        array('%width%' => $this->maxWidth, '%height%' => $this->maxHeight)
                    );
                }
            }

            $this->image = $image->image;
        }
    }


    public function save()
    {
        if ($this->image) {
            $this->onProcessImage($this->image);

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
                    $this->image,
                    $this->resizeToWidth,
                    $this->resizeToHeight,
                    false,
                    $this->resizeImageToolsScaleMode,
                    $this->resizeImageToolsBackground
                );

            } else {
                $image = $this->image;
            }

            if ($oldSaveFilename && $oldSaveFilename != $saveFilename) {
                unlink($this->saveDirectory . $oldSaveFilename);
            }

            if ($this->saveFormat == 'jpg') {
                imagejpeg($image, $this->saveDirectory . $saveFilename, $this->saveQuality);

            } elseif ($this->saveFormat == 'png') {
                imagepng($image, $this->saveDirectory . $saveFilename);
            }

            $this->onImageProcessed($this->image);

            if ($image != $this->image) {
                imagedestroy($this->image);
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

    protected function getDefaultRenderer()
    {
        return new ImageContentRenderer();
    }
}
