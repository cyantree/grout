<?php
namespace Cyantree\Grout\Types;

class ImageToolsCheckFileResult
{
    const ERROR_FILESIZE = 'filesize';
    const ERROR_INVALID = 'invalid';
    const ERROR_MIN_WIDTH = 'minWidth';
    const ERROR_MIN_HEIGHT = 'minHeight';
    const ERROR_MAX_WIDTH = 'maxWidth';
    const ERROR_MAX_HEIGHT = 'maxHeight';
    const ERROR_MIN_SIZE = 'minSize';
    const ERROR_MAX_SIZE = 'maxSize';
    const ERROR_SIZE = 'size';
    const ERROR_ASPECT_RATIO = 'aspectRation';
    const ERROR_MIN_ASPECT_RATIO = 'minAspectRatio';
    const ERROR_MAX_ASPECT_RATIO = 'maxAspectRatio';

    /** @var bool */
    public $success = false;

    /** @var resource */
    public $image;
    public $type;
    public $width;
    public $height;

    public $errors = array();
}