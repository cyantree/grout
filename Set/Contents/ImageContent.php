<?php
namespace Cyantree\Grout\Set\Contents;

use Cyantree\Grout\Filter\ArrayFilter;
use Cyantree\Grout\Set\Content;
use Cyantree\Grout\Set\ContentRendererSettings\ImageContentRendererSettings;
use Cyantree\Grout\Tools\FileTools;
use Cyantree\Grout\Tools\ImageTools;
use Cyantree\Grout\Types\FileUpload;
use Cyantree\Grout\Types\ImageToolsCheckFileResult;

class ImageContent extends Content
{
    public $required = false;

    public $maxFilesize = null;
    public $minWidth = null;
    public $minHeight = null;
    public $maxWidth = null;
    public $maxHeight = null;
    public $minAspectRatio = null;
    public $maxAspectRatio = null;
    public $allowRotated = false;

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

    /** @var ImageContentRendererSettings */
    public $rendererSettings;

    /** @var FileUpload */
    public $uploadedFile;

    protected $image;

    public function __construct()
    {
        parent::__construct();

        $this->rendererSettings = new ImageContentRendererSettings();
    }

    protected function getDefaultErrorMessage($code)
    {
        static $errors = null;

        if ($errors === null) {
            $errors = new ArrayFilter(array(
                    'required' => _('Im Feld „%name%“ wurde kein Bild ausgewählt.'),
                    'invalid' => _('Im Feld „%name%“ wurde kein gültiges Bild ausgewählt.'),
                    'filesize' => _('Das Bild „%name%“ darf nicht größer als %size% MB sein.'),
                    'aspectRatio' => _('Das Seitenverhältnis des Bildes „%name%“ muss zwischen %minRatio% und %maxRatio% liegen.'),
                    'minAspectRatio' => _('Das Seitenverhältnis des Bildes „%name%“ muss wenigstens %ratio% betragen.'),
                    'maxAspectRatio' => _('Das Seitenverhältnis des Bildes „%name%“ darf höchstens %ratio% betragen.'),
                    'maxSize' => _('Das Bild „%name%“ darf nicht größer als %width%x%height% Pixel sein.'),
                    'maxWidth' => _('Das Bild „%name%“ darf nicht breiter als %width% Pixel sein.'),
                    'maxHeight' => _('Das Bild „%name%“ darf nicht höher als %height% Pixel sein.'),
                    'minSize' => _('Das Bild „%name%“ muss mindestens %width%x%height% Pixel groß sein.'),
                    'minWidth' => _('Die Breite des Bildes „%name%“ darf nicht weniger als %width% Pixel betragen.'),
                    'minHeight' => _('Die Höhe des Bildes „%name%“ darf nicht weniger als %height% Pixel betragen.'),
            ));
        }
        return $errors->get($code);
    }

    public function getImagePath()
    {
        return $this->getImagePathByValue($this->value);
    }

    public function getImageUrl()
    {
        return $this->getImageUrlByValue($this->value);
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
            $this->postError('required');
            return;
        }

        if ($this->uploadedFile) {
            $image = ImageTools::checkFile(
                $this->uploadedFile->file,
                $this->uploadedFile->name,
                $this->maxFilesize,
                $this->minWidth,
                $this->minHeight,
                $this->maxWidth,
                $this->maxHeight,
                $this->minAspectRatio,
                $this->maxAspectRatio,
                $this->allowRotated
            );

            if (!$image->success) {
                $errors = new ArrayFilter(array_flip($image->errors));
                
                if ($errors->has(ImageToolsCheckFileResult::ERROR_FILESIZE)) {
                    $filesize = round($this->maxFilesize / 1024 / 1024 * 10) / 10;

                    $this->postError('filesize', array('%size%' => $filesize));
                }
                
                if ($errors->has(ImageToolsCheckFileResult::ERROR_INVALID)) {
                    $this->postError('invalid');
                }
                
                if ($this->minWidth && $this->minHeight && $errors->has(ImageToolsCheckFileResult::ERROR_MIN_SIZE)) {
                    $this->postError('minSize', array('%width%' => $this->minWidth, '%height%' => $this->minHeight));
                    
                } elseif ($this->minWidth && $errors->has(ImageToolsCheckFileResult::ERROR_MIN_WIDTH)) {
                    $this->postError('minWidth', array('%width%' => $this->minWidth));
                    
                } elseif ($this->minHeight && $errors->has(ImageToolsCheckFileResult::ERROR_MIN_HEIGHT)) {
                    $this->postError('minHeight', array('%height%' => $this->minHeight));
                }

                if ($this->maxWidth && $this->maxHeight && $errors->has(ImageToolsCheckFileResult::ERROR_MAX_SIZE)) {
                    $this->postError('maxSize', array('%width%' => $this->maxWidth, '%height%' => $this->maxHeight));

                } elseif ($this->maxWidth && $errors->has(ImageToolsCheckFileResult::ERROR_MAX_WIDTH)) {
                    $this->postError('maxWidth', array('%width%' => $this->maxWidth));

                } elseif ($this->maxHeight && $errors->has(ImageToolsCheckFileResult::ERROR_MAX_HEIGHT)) {
                    $this->postError('maxHeight', array('%height%' => $this->maxHeight));
                }

                if ($this->minAspectRatio && $this->maxAspectRatio && $errors->has(ImageToolsCheckFileResult::ERROR_ASPECT_RATIO)) {
                    $this->postError(
                        'aspectRatio',
                        array(
                            '%minRatio%' => number_format($this->minAspectRatio, 1),
                            '%maxRatio%' => number_format($this->maxAspectRatio, 1)
                        )
                    );

                } elseif ($this->minAspectRatio && $errors->has(ImageToolsCheckFileResult::ERROR_MIN_ASPECT_RATIO)) {
                    $this->postError('minAspectRatio', array('%min%' => number_format($this->minAspectRatio, 1)));

                } elseif ($this->maxAspectRatio && $errors->has(ImageToolsCheckFileResult::ERROR_MAX_ASPECT_RATIO)) {
                    $this->postError('maxAspectRatio', array('%max%' => number_format($this->maxAspectRatio, 1)));
                }
            }

            $this->image = $image->image;
        }
    }

    public function save()
    {
        if (!$this->image) {
            return;
        }

        $oldValue = $this->value;
        $this->value = $this->generateValue();

        $this->image = $this->processImage($this->image);

        $this->saveImage();

        if ($oldValue != $this->value) {
            $this->onImageChanged($oldValue);
        }

        imagedestroy($this->image);

        $this->uploadedFile->delete();
    }

    public function getImagePathByValue($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->valueContainsExtension) {
            return $this->saveDirectory . $value;

        } else {
            return $this->saveDirectory . $value . '.' . $this->saveFormat;
        }
    }

    public function getImageUrlByValue($value)
    {
        if (!$value) {
            return null;
        }

        if ($this->valueContainsExtension) {
            return $this->saveDirectoryUrl . $value;

        } else {
            return $this->saveDirectoryUrl . $value . '.' . $this->saveFormat;
        }
    }

    protected function generateValue()
    {
        if ($this->saveFilename) {
            return $this->saveFilename;

        } else {
            $value = FileTools::createUniqueFilename(
                $this->saveDirectory,
                '.' . $this->saveFormat,
                32,
                true);

            if ($this->valueContainsExtension) {
                $value .= '.' . $this->saveFormat;
            }

            return $value;
        }
    }

    protected function saveImage()
    {
        $path = $this->getImagePathByValue($this->value);

        if ($this->saveFormat == 'jpg') {
            imagejpeg($this->image, $path, $this->saveQuality);

        } elseif ($this->saveFormat == 'png') {
            imagepng($this->image, $path);
        }
    }

    protected function processImage($image)
    {
        if ($this->resizeToWidth) {
            $newImage = ImageTools::resizeImage(
                $image,
                $this->resizeToWidth,
                $this->resizeToHeight,
                false,
                $this->resizeImageToolsScaleMode,
                $this->resizeImageToolsBackground
            );

            imagedestroy($image);

            return $newImage;

        } else {
            return $image;
        }
    }

    public function onDelete()
    {
        if ($this->value) {
            unlink($this->getImagePathByValue($this->value));
        }
    }

    protected function onImageChanged($oldValue)
    {
        if ($oldValue && $oldValue != $this->value) {
            unlink($this->getImagePathByValue($oldValue));
        }
    }
}
