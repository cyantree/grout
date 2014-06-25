<?php
namespace Cyantree\Grout\Tools;

use Cyantree\Grout\Types\ImageToolsCheckFileResult;

class ImageTools
{
    const MODE_SCALE_CROP = 1;
    const MODE_EXACT = 2;
    const MODE_CROP = 3;
    const MODE_SCALE_PAD = 4;
    const MODE_FIT = 5;

    public static function copyToImage($source, $target, $positionFactorX, $positionFactorY)
    {
        $sW = imagesx($source);
        $sH = imagesy($source);
        $tW = imagesx($target);
        $tH = imagesy($target);

        $x = round(($tW - $sW) * $positionFactorX);
        $y = round(($tH - $sH) * $positionFactorY);

        imagealphablending($target, true);
        imagecopy($target, $source, $x, $y, 0, 0, $sW, $sH);
    }

    public static function createImage($width, $height, $backgroundColor = 0x00000000, $useTransparency = true)
    {
        $i = imagecreatetruecolor($width, $height);

        imagealphablending($i, false);

        if (!is_array($backgroundColor)) {
            $backgroundColor = self::colorHexToRgba($backgroundColor);
        }

        $backgroundColor = imagecolorallocatealpha($i, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b'], 127 - floor($backgroundColor['a'] / 2));

        imagefilledrectangle($i, 0, 0, $width, $height, $backgroundColor);

        if ($useTransparency) {
            imagesavealpha($i, true);

            imagealphablending($i, true);
        }

        return $i;
    }

    /** Resizes an image
     */
    public static function resizeImage($image, $newWidth, $newHeight, $autoRotate = false, $mode = ImageTools::MODE_SCALE_CROP, $backgroundColor = 0x00000000)
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        $sourcePar = $sourceWidth / $sourceHeight;
        $newPar = $newWidth / $newHeight;
        $sourceX = $newX = 0;
        $sourceY = $newY = 0;

        if ($autoRotate && (($sourcePar > 1 && $newPar < 1) || ($sourcePar < 1 && $newPar > 1))) {
            $t = $newWidth;
            $newWidth = $newHeight;
            $newHeight = $t;
            $newPar = $newWidth / $newHeight;
        }

        $targetWidth = $newWidth;
        $targetHeight = $newHeight;

        // MODE_EXACT gets handled through default variables

        if ($mode == ImageTools::MODE_CROP) {
            if ($sourceWidth > $newWidth) {
                $sourceX = ($sourceWidth - $newWidth) >> 1;
                $sourceWidth = $newWidth;
            } else {
                $targetWidth = $sourceWidth;
                $newX = ($newWidth - $sourceWidth) >> 1;
            }

            if ($sourceHeight > $newHeight) {
                $sourceY = ($sourceHeight - $newHeight) >> 1;
                $sourceHeight = $newHeight;
            } else {
                $targetHeight = $sourceHeight;
                $newY = ($newHeight - $sourceHeight) >> 1;
            }

        } elseif ($mode == ImageTools::MODE_SCALE_CROP) {
            if ($sourcePar > $newPar) {
                $orgWidth = $sourceWidth;
                $sourceWidth = $sourceHeight * $newPar;
                $sourceX = ($orgWidth - $sourceWidth) >> 1;
            } else {
                $orgHeight = $sourceHeight;
                $sourceHeight = $sourceWidth / $newPar;
                $sourceY = ($orgHeight - $sourceHeight) >> 1;
            }

        } elseif ($mode == ImageTools::MODE_SCALE_PAD) {
            if ($sourcePar > $newPar) {
                $targetHeight = $targetWidth / $sourcePar;
                $newY = ($newHeight - $targetHeight) >> 1;
            } else {
                $targetWidth = $targetHeight * $sourcePar;
                $newX = ($newWidth - $targetWidth) >> 1;
            }
        } elseif ($mode == ImageTools::MODE_FIT) {
            if ($sourcePar > $newPar) {
                $targetHeight = $newHeight = round($newWidth / $sourcePar);
            } else {
                $targetWidth = $newWidth = round($newHeight * $sourcePar);
            }
        }

        $new = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($new, false);

        $backgroundColor = self::colorHexToRgba($backgroundColor);
        $backgroundColor = imagecolorallocatealpha($new, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b'], 127 - floor($backgroundColor['a'] / 2));

        imagefilledrectangle($new, 0, 0, $newWidth, $newHeight, $backgroundColor);

        imagesavealpha($new, true);

        imagecopyresampled($new, $image, $newX, $newY, $sourceX, $sourceY, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $new;
    }

    public static function calcSizeFromImage($image, $maxWidth, $maxHeight, $allowUpScale = true, $allowRotation = false)
    {
        return ImageTools::calcSize(imagesx($image), imagesy($image), $maxWidth, $maxHeight, $allowUpScale, $allowRotation);
    }

    public static function calcSize($width, $height, $maxWidth, $maxHeight, $allowUpScale = true, $allowRotation = false)
    {
        $inWidth = $width;
        $inHeight = $height;

        $parIn = $width / $height;
        $parOut = $maxWidth / $maxHeight;

        if ($allowRotation && (($parIn >= 1 && $parOut <= 1) || ($parIn <= 1 && $parOut >= 1))) {
            $temp = $maxWidth;
            $maxWidth = $maxHeight;
            $maxHeight = $temp;
            $parOut = $maxWidth / $maxHeight;
        }

        if ($parIn > $parOut) {
            $width = $maxWidth;
            $height = $width / $parIn;
        } else {
            $height = $maxHeight;
            $width = $height * $parIn;
        }

        $width = round($width);
        $height = round($height);

        if ($width > $inWidth && !$allowUpScale) {
            $width = $inWidth;
            $height = $inHeight;
        }

        return array($width, $height);
    }

    public static function drawText($text, $ttfFont, $size, $textColor = 0x000000ff, $backgroundColor = 0x00000000, $additionalConfigs = null)
    {
        $text = StringTools::toNumericEntities($text);

        $box = imagettfbbox($size, 0, $ttfFont, $text);

        $yOffset = $box[1];

        $width = round($box[2] - $box[0] + $size * .2);
        $height = round($box[1] - $box[7] + $size * .2);
        $xOffset = $width > 50 ? 50 : round($width / 2);

        $image = imagecreate($width, $height);

        $cropCol = imagecolorallocate($image, 255, 0, 255);

        imagefilledrectangle($image, 0, 0, $width, $height, $cropCol);

        imagettftext($image, $size, 0, $xOffset, $height - $yOffset, imagecolorallocate($image, 255, 255, 255), $ttfFont, $text);

        $croppingPrecision = ArrayTools::get($additionalConfigs, 'croppingPrecision', 2);

        // Crop left
        $cropLeft = -1;
        for ($x = 0; $x < 50 + $width / 2; $x += $croppingPrecision) {
            if ($x >= $width) {
                $cropLeft = $x - 1;
                break;
            }

            for ($y = 0; $y < $height; $y += $croppingPrecision) {
                $col = imagecolorat($image, $x, $y);

                if ($col != 0) {
                    $cropLeft = $x - 1;
                    break 1;
                }
            }
            if ($cropLeft >= 0) break;
        }

        $xOffset = $xOffset - $cropLeft - 1;
        $yOffset = 0;

        $temp = imagettftext($image, $size, 0, 0, 0, $cropCol, $ttfFont, $text);

        if (ArrayTools::get($additionalConfigs, 'slim'))
            $offset = $temp;
        else
            $offset = imagettftext($image, $size, 0, 0, 0, $cropCol, $ttfFont, 'BTj&#' . ord(utf8_decode('`')) . ';');

        $width = $temp[2] - $temp[0];
        $height = abs($offset[5] - $offset[1]);

        if ($margin = ArrayTools::get($additionalConfigs, 'safeMargin')) {
            if (is_array($margin)) {
                $width += $margin[1] + $margin[3];
                $xOffset += $margin[3];

                $height += $margin[0] + $margin[2];
                $yOffset += $margin[0];

            } else {
                $width += $margin * 2;
                $height += $margin * 2;

                $xOffset += $margin;
                $yOffset += $margin;
            }
        }

        imagedestroy($image);

        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $backgroundColor = self::colorHexToRgba($backgroundColor);
        $backgroundColor = imagecolorallocatealpha($image, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b'], 127 - floor($backgroundColor['a'] / 2));

        $textColor = self::colorHexToRgba($textColor);
        $textColor = imagecolorallocatealpha($image, $textColor['r'], $textColor['g'], $textColor['b'], 127 - floor($textColor['a'] / 2));

        imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);
        imagealphablending($image, true);
        imagettftext($image, $size, 0, $xOffset, -$offset[5] + $yOffset, $textColor, $ttfFont, $text);

        return $image;
    }

    public static function checkFile($file, $filename = null, $maxFilesize = null, $minWidth = null, $minHeight = null, $maxWidth = null, $maxHeight = null, $minAspectRatio = null, $maxAspectRatio = null, $allowRotation = false)
    {
        $result = new ImageToolsCheckFileResult();

        // Check filesize
        if ($maxFilesize && filesize($file) > $maxFilesize) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_FILESIZE;

            return $result;
        }

        if (!$filename) $filename = $file;

        // Get extension
        $extension = explode('.', $filename);
        $count = count($extension);
        if($count == 1){
            $result->errors[] = ImageToolsCheckFileResult::ERROR_INVALID;

            return $result;
        }else{
            $extension = strtolower($extension[$count - 1]);
        }

        $type = null;

        // Load image
        if ($extension == 'jpg' || $extension == 'jpeg') {
            $image = @imagecreatefromjpeg($file);
            $type = 'jpg';
        } else if ($extension == 'gif') {
            $image = @imagecreatefromgif($file);
            $type = 'gif';
        } else if ($extension == 'png') {
            $image = @imagecreatefrompng($file);
            $type = 'png';
        } else $image = null;

        if (!$image) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_INVALID;

            return $result;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $imageAspectRatio = $width / $height;

        if ($allowRotation) {
            $aspectRatio = $minWidth !== null ? $minWidth / $minHeight :
                  ($maxWidth !== null ? $maxWidth / $maxHeight :
                        ($minAspectRatio !== null ? $minAspectRatio :
                              ($maxAspectRatio !== null ? $maxAspectRatio : null)));

            if ($aspectRatio !== null && ($imageAspectRatio > 1 && $aspectRatio < 1 || $imageAspectRatio < 1 && $aspectRatio > 1)) {
                $temp = $width;
                $width = $height;
                $height = $temp;
                $imageAspectRatio = $width / $height;
            }
        }

        $triggerErrorMinSize = $triggerErrorMaxSize = false;
        $triggerErrorAspectRatio = false;

        if ($minWidth !== null && $width < $minWidth) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MIN_WIDTH;
            $triggerErrorMinSize = true;
        } else if ($maxWidth !== null && $width > $maxWidth) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MAX_WIDTH;
            $triggerErrorMaxSize = true;
        }

        if ($minHeight !== null && $height < $minHeight) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MIN_HEIGHT;
            $triggerErrorMinSize = true;
        } else if ($maxHeight !== null && $height > $maxHeight) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MAX_HEIGHT;
            $triggerErrorMaxSize = true;
        }

        if ($minAspectRatio !== null && $imageAspectRatio < $minAspectRatio) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MIN_ASPECT_RATIO;
            $triggerErrorAspectRatio = true;
        } else if ($maxAspectRatio !== null && $imageAspectRatio > $maxAspectRatio) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MAX_ASPECT_RATIO;
            $triggerErrorAspectRatio = true;
        }

        if ($triggerErrorMinSize) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MIN_SIZE;
        }
        if ($triggerErrorMaxSize) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_MAX_SIZE;
        }
        if ($triggerErrorMinSize || $triggerErrorMaxSize) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_SIZE;
        }
        if ($triggerErrorAspectRatio) {
            $result->errors[] = ImageToolsCheckFileResult::ERROR_ASPECT_RATIO;
        }

        if (count($result->errors)) imagedestroy($image);
        else {
            $result->success = true;
            $result->path = $file;
            $result->image = $image;
            $result->type = $type;
            $result->width = $width;
            $result->height = $height;
        }

        return $result;
    }

    public static function colorHexToRgba($color)
    {
        return array('r' => $color >> 24 & 0xFF, 'g' => $color >> 16 & 0xFF, 'b' => $color >> 8 & 0xFF, 'a' => $color & 0xFF);
    }

    public static function colorRgbaToHex($color)
    {
        return $color['a'] + ($color['b'] << 8) + ($color['g'] << 16) + ($color['r'] << 24);
    }
}