<?php

namespace Core\Model\Utils;

class ImageUtils
{

    const string IMG_JPG  = 'jpg';
    const string IMG_JPEG = 'jpeg';
    const string IMG_GIF  = 'gif';
    const string IMG_PNG  = 'png';
    const string IMG_WEBP = 'webp';

    /**
     * Generate Webp image format
     * Uses either Imagick or imagewebp to generate webp image
     *
     * @param string $folder              Path to image being converted.
     * @param string $file                file name
     * @param int    $compression_quality Quality ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file).
     *
     * @return false|string Returns path to generated webp image, otherwise returns false.
     */
    function generateWebpImage(string $folder, string $file, int $compression_quality = 90): false|string
    {
        $previousPath = $folder . $file;

        // check if file exists
        if (!file_exists($previousPath)) {
            return false;
        }

        // If output file already exists return path
        $ext     = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $newFile = str_replace('.' . $ext, '.webp', $file);
        $newPath = $folder . $newFile;
        if (file_exists($newPath)) {
            return $newFile;
        }

        $fileType = strtolower(pathinfo($previousPath, PATHINFO_EXTENSION));
        if (function_exists('imagewebp')) {
            switch ($fileType) {
                case self::IMG_JPG:
                case self::IMG_JPEG:
                    $image = imagecreatefromjpeg($previousPath);
                    break;

                case self::IMG_PNG:
                    $image = imagecreatefrompng($previousPath);
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                    break;

                case self::IMG_GIF:
                    $image = imagecreatefromgif($previousPath);
                    break;
                default:
                    return false;
            }

            // Save the image
            if ($image) {
                $result = imagewebp($image, $newPath, $compression_quality);
                if (false === $result) {
                    return false;
                }

                return $newFile;
            }
        }

        return false;
    }

}
