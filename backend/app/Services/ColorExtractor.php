<?php

namespace App\Services;

class ColorExtractor
{
    /**
     * Extract dominant colors from an image file using PHP GD.
     *
     * @param string $imagePath
     * @param int $numColors
     * @return array
     */
    public static function extract(string $imagePath, int $numColors = 5): array
    {
        if (!file_exists($imagePath)) {
            return self::getFallbackPalette($numColors);
        }

        if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng') && !function_exists('imagecreatetruecolor')) {
            return self::getFallbackPalette($numColors);
        }

        $info = @getimagesize($imagePath);
        if (!$info) {
            return self::getFallbackPalette($numColors);
        }

        $type = $info[2];
        $img = null;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $img = @imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $img = @imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $img = @imagecreatefromwebp($imagePath);
                }
                break;
        }

        if (!$img) {
            return self::getFallbackPalette($numColors);
        }

        // Resize the image to 30x30 pixels.
        // This naturally groups and averages local colors, and speeds up calculation.
        $width = 30;
        $height = 30;
        $resizedImg = imagecreatetruecolor($width, $height);

        // Preserve transparency if applicable
        imagealphablending($resizedImg, false);
        imagesavealpha($resizedImg, true);

        imagecopyresampled($resizedImg, $img, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

        $colors = [];
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $colorIndex = imagecolorat($resizedImg, $x, $y);
                $rgba = imagecolorsforindex($resizedImg, $colorIndex);

                // Skip fully transparent pixels (alpha = 127 in GD is fully transparent)
                if ($rgba['alpha'] >= 110) {
                    continue;
                }

                // Quantize RGB values to the nearest multiple of 32 to group similar shades.
                $r = round($rgba['red'] / 32) * 32;
                $g = round($rgba['green'] / 32) * 32;
                $b = round($rgba['blue'] / 32) * 32;

                $r = min(255, max(0, $r));
                $g = min(255, max(0, $g));
                $b = min(255, max(0, $b));

                $hex = sprintf("#%02x%02x%02x", $r, $g, $b);

                if (isset($colors[$hex])) {
                    $colors[$hex]++;
                } else {
                    $colors[$hex] = 1;
                }
            }
        }

        // Clean up memory
        imagedestroy($img);
        imagedestroy($resizedImg);

        if (empty($colors)) {
            return self::getFallbackPalette($numColors);
        }

        // Sort colors by frequency descending
        arsort($colors);

        // Extract hex values
        $palette = array_keys(array_slice($colors, 0, $numColors, true));

        // If we didn't extract enough colors, fill with fallbacks
        while (count($palette) < $numColors) {
            foreach (self::getFallbackPalette($numColors) as $fbColor) {
                if (!in_array($fbColor, $palette)) {
                    $palette[] = $fbColor;
                }
                if (count($palette) >= $numColors) {
                    break;
                }
            }
        }

        return $palette;
    }

    /**
     * Determine if a hex color is dark or light using YIQ relative luminance.
     *
     * @param string $hexColor
     * @return bool True if dark (requires light text), false if light (requires dark text)
     */
    public static function isDark(string $hexColor): bool
    {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 6) {
            $r = hexdec(substr($hexColor, 0, 2));
            $g = hexdec(substr($hexColor, 2, 2));
            $b = hexdec(substr($hexColor, 4, 2));
        } elseif (strlen($hexColor) === 3) {
            $r = hexdec(str_repeat(substr($hexColor, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hexColor, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hexColor, 2, 1), 2));
        } else {
            return false; // Default fallback
        }

        // YIQ relative luminance formula
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Threshold 150 works well to guarantee high readability contrast
        return $yiq < 150;
    }

    /**
     * A safe fallback color palette.
     *
     * @param int $count
     * @return array
     */
    public static function getFallbackPalette(int $count = 5): array
    {
        $defaults = ['#ffffff', '#1a1a1a', '#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#06b6d4'];
        return array_slice($defaults, 0, $count);
    }
}
