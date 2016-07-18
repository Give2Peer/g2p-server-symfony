<?php

namespace Give2Peer\Give2PeerBundle\Service;


/**
 * Depends on `php-gd` !
 */
class Thumbnailer
{
    /**
     * Generates a JPG square thumbnail from the $source image.
     * It will take the biggest square that fits in the center of the image.
     *
     * Note: transparency will be cast to black.
     *
     * @param $source
     * @param $destination
     * @param $sideLength
     * @param string $subtype the image mime subtype of the source.
     * @throws \Exception
     */
    static function generateSquare($source, $destination, $sideLength, $subtype)
    {
        switch ($subtype) {
            case 'jpg';
            case 'jpeg';
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'png';
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'gif';
                $sourceImage = imagecreatefromgif($source);
                break;
            case 'webp';
                // I have 'undefined function' in my IDE ?! ; it works, though. Kinda.
                $sourceImage = imagecreatefromwebp($source);
                break;
            default:
                throw new \Exception("Unsupported image subtype: '$subtype'.");

        }

        $width  = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        $smallestSide = min($width, $height);

        $originX = 0; $originY = 0;
        if ($width < $height) {
            $originY = floor(($height - $smallestSide) / 2);
        }
        else if ($width > $height) {
            $originX = floor(($width - $smallestSide) / 2);
        }

        $virtualImage = imagecreatetruecolor($sideLength, $sideLength);
        imagecopyresampled(
            $virtualImage, $sourceImage,
            0, 0, $originX, $originY,
            $sideLength, $sideLength,
            $smallestSide, $smallestSide
        );
        imagejpeg($virtualImage, $destination);
    }
}