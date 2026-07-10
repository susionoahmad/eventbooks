<?php

namespace App\Services;

class ThumbnailGenerator
{
    /**
     * Crop an image to a 1.91:1 ratio (1200x630px) and compress it to JPEG format.
     *
     * @param string $sourcePath Path to the original image
     * @param string $outputPath Path where the compressed thumbnail should be saved
     * @return bool True on success, false on failure
     */
    public static function generate(string $sourcePath, string $outputPath): bool
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        // Check if GD is available
        if (!extension_loaded('gd') && !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Load the source image depending on MIME type
        $srcImg = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImg = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $srcImg = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $srcImg = @imagecreatefromwebp($sourcePath);
                }
                break;
        }

        if (!$srcImg) {
            return false;
        }

        // Open Graph standard dimensions for Large Card (landscape 1.91:1)
        $targetWidth = 1200;
        $targetHeight = 630;
        $targetRatio = $targetWidth / $targetHeight;

        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            // Source is wider than target ratio - crop left and right sides
            $croppedWidth = (int) round($srcHeight * $targetRatio);
            $croppedHeight = $srcHeight;
            $srcX = (int) round(($srcWidth - $croppedWidth) / 2);
            $srcY = 0;
        } else {
            // Source is taller than target ratio - crop top and bottom sides
            $croppedWidth = $srcWidth;
            $croppedHeight = (int) round($srcWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int) round(($srcHeight - $croppedHeight) / 2);
        }

        // Create a blank truecolor image
        $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$dstImg) {
            imagedestroy($srcImg);
            return false;
        }

        // Fill with white background (in case of transparent backgrounds in PNG/GIF/WEBP)
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefill($dstImg, 0, 0, $white);

        // Copy and resize the cropped portion
        $success = imagecopyresampled(
            $dstImg,
            $srcImg,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $croppedWidth,
            $croppedHeight
        );

        if ($success) {
            // Save as JPEG with 70% quality (ideal compression for crawler load speed)
            $success = @imagejpeg($dstImg, $outputPath, 70);
        }

        // Clean up memory
        imagedestroy($srcImg);
        imagedestroy($dstImg);

        return $success;
    }
}
