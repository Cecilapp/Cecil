<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Asset;

use Cecil\Asset;
use Cecil\Exception\RuntimeException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

/**
 * Image Asset class.
 *
 * Provides methods to manipulate images, such as resizing, cropping, converting,
 * and generating data URLs.
 *
 * This class uses the Intervention Image library to handle image processing.
 * It supports both GD and Imagick drivers, depending on the available PHP extensions.
 */
class Image
{
    /**
     * Create new manager instance with available driver.
     */
    private static function manager(): ImageManager
    {
        $driver = null;

        // ImageMagick is available? (for a future quality option)
        if (\extension_loaded('imagick') && class_exists('Imagick')) {
            $driver = ImagickDriver::class;
        }
        // Use GD, because it's the faster driver
        if (\extension_loaded('gd') && \function_exists('gd_info')) {
            $driver = GdDriver::class;
        }

        if ($driver) {
            return ImageManager::withDriver(
                $driver,
                [
                    'autoOrientation' => true,
                    'decodeAnimation' => true,
                    'blendingColor' => 'ffffff',
                    'strip' => true, // remove metadata
                ]
            );
        }

        throw new RuntimeException('PHP GD (or Imagick) extension is required.');
    }

    /**
     * Scales down an image Asset to the given width, keeping the aspect ratio.
     *
     * @throws RuntimeException
     */
    public static function resize(Asset $asset, int $width, int $quality): string
    {
        try {
            // creates image object from source
            $image = self::manager()->read($asset['content']);
            // resizes to $width with constraint the aspect-ratio and unwanted upsizing
            $image->scaleDown(width: $width);
            // return image data
            return (string) $image->encodeByMediaType(
                $asset['subtype'],
                /** @scrutinizer ignore-type */
                progressive: true,
                /** @scrutinizer ignore-type */
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Asset "%s" can\'t be resized: %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Crops an image Asset to the given width and height, keeping the aspect ratio.
     *
     * @throws RuntimeException
     */
    public static function cover(Asset $asset, int $width, int $height, int $quality): string
    {
        try {
            // creates image object from source
            $image = self::manager()->read($asset['content']);
            // turns an animated image (i.e GIF) into a static image
            if ($image->isAnimated()) {
                $image = $image->removeAnimation('25%'); // use 25% to avoid an "empty" frame
            }
            // crops the image
            $image->cover(
                width: $width,
                height: $height,
                position: 'center'
            );
            // return image data
            return (string) $image->encodeByMediaType(
                $asset['subtype'],
                /** @scrutinizer ignore-type */
                progressive: true,
                /** @scrutinizer ignore-type */
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Asset "%s" can\'t be cropped: %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Makes an image Asset maskable, meaning it can be used as a PWA icon.
     *
     * @throws RuntimeException
     */
    public static function maskable(Asset $asset, int $quality, int $padding): string
    {
        try {
            // creates image object from source
            $source = self::manager()->read($asset['content']);
            // creates a new image with the dominant color as background
            // and the size of the original image plus the padding
            $image = self::manager()->create(
                width: (int) round($asset['width'] * (1 + $padding / 100), 0),
                height: (int) round($asset['height'] * (1 + $padding / 100), 0)
            )->fill(self::getDominantColor($asset));
            // inserts the original image in the center
            $image->place(
                $source,
                position: 'center'
            );
            // scales down the new image to the original image size
            $image->scaleDown(width: $asset['width']);
            // return image data
            return (string) $image->encodeByMediaType(
                $asset['subtype'],
                /** @scrutinizer ignore-type */
                progressive: true,
                /** @scrutinizer ignore-type */
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to make Asset "%s" maskable: %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Converts an image Asset to the target format.
     *
     * @throws RuntimeException
     */
    public static function convert(Asset $asset, string $format, int $quality): string
    {
        try {
            $image = self::manager()->read($asset['content']);

            if (!\function_exists("image$format")) {
                throw new RuntimeException(\sprintf('Function "image%s" is not available.', $format));
            }

            return (string) $image->encodeByExtension(
                $format,
                /** @scrutinizer ignore-type */
                progressive: true,
                /** @scrutinizer ignore-type */
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to convert "%s" to %s: %s', $asset['path'], $format, $e->getMessage()));
        }
    }

    /**
     * Returns the Data URL (encoded in Base64).
     *
     * @throws RuntimeException
     */
    public static function getDataUrl(Asset $asset, int $quality): string
    {
        try {
            $image = self::manager()->read($asset['content']);

            return (string) $image->encode(new AutoEncoder(quality: $quality))->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get Data URL of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns the dominant RGB color of an image asset.
     *
     * @throws RuntimeException
     */
    public static function getDominantColor(Asset $asset): string
    {
        try {
            $image = self::manager()->read(self::resize($asset, 100, 50));

            return $image->reduceColors(1)->pickColor(0, 0)->toString();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get dominant color of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns a Low Quality Image Placeholder (LQIP) as data URL.
     *
     * @throws RuntimeException
     */
    public static function getLqip(Asset $asset): string
    {
        try {
            $image = self::manager()->read(self::resize($asset, 100, 50));

            return (string) $image->blur(50)->encode()->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to create LQIP of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Build the `srcset` HTML attribute for responsive images.
     * e.g.: `srcset="/img-480.jpg 480w, /img-800.jpg 800w"`.
     *
     * $widths is an array of widths to include in the `srcset`.
     * If $notEmpty is true, the source image is always added to the `srcset`.
     *
     * @throws RuntimeException
     */
    public static function buildHtmlSrcset(Asset $asset, array $widths, $notEmpty = false): string
    {
        if (!self::isImage($asset)) {
            throw new RuntimeException(\sprintf('Unable to build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = '';
        $widthMax = 0;
        sort($widths, SORT_NUMERIC);
        $widths = array_reverse($widths);
        foreach ($widths as $width) {
            if ($asset['width'] < $width) {
                continue;
            }
            $img = $asset->resize($width);
            $srcset .= \sprintf('%s %sw, ', (string) $img, $width);
            $widthMax = $width;
        }
        // adds source image
        if ((!empty($srcset) || $notEmpty) && ($asset['width'] < max($widths) && $asset['width'] != $widthMax)) {
            $srcset .= \sprintf('%s %sw', (string) $asset, $asset['width']);
        }

        return rtrim($srcset, ', ');
    }

    /**
     * Returns the value from the `$sizes` array if the class exists, otherwise returns the default size.
     */
    public static function getHtmlSizes(string $class, array $sizes = []): string
    {
        $result = '';
        $classArray = explode(' ', $class);
        foreach ($classArray as $class) {
            if (\array_key_exists($class, $sizes)) {
                $result = $sizes[$class] . ', ';
            }
        }
        if (!empty($result)) {
            return trim($result, ', ');
        }

        return $sizes['default'] ?? '100vw';
    }

    /**
     * Checks if an asset is an animated GIF.
     */
    public static function isAnimatedGif(Asset $asset): bool
    {
        // an animated GIF contains multiple "frames", with each frame having a header made up of:
        // 1. a static 4-byte sequence (\x00\x21\xF9\x04)
        // 2. 4 variable bytes
        // 3. a static 2-byte sequence (\x00\x2C)
        $count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', (string) $asset['content']);

        return $count > 1;
    }

    /**
     * Returns true if asset is a SVG.
     */
    public static function isSVG(Asset $asset): bool
    {
        return \in_array($asset['subtype'], ['image/svg', 'image/svg+xml']) || $asset['ext'] == 'svg';
    }

    /**
     * Returns true if asset is an ICO.
     */
    public static function isIco(Asset $asset): bool
    {
        return \in_array($asset['subtype'], ['image/x-icon', 'image/vnd.microsoft.icon']) || $asset['ext'] == 'ico';
    }

    /**
     * Asset is a valid image?
     */
    public static function isImage(Asset $asset): bool
    {
        if ($asset['type'] !== 'image' || self::isSVG($asset) || self::isIco($asset)) {
            return false;
        }

        return true;
    }

    /**
     * Returns SVG attributes.
     *
     * @return \SimpleXMLElement|false
     */
    public static function getSvgAttributes(Asset $asset)
    {
        if (!self::isSVG($asset)) {
            return false;
        }

        if (false === $xml = simplexml_load_string($asset['content'] ?? '')) {
            return false;
        }

        return $xml->attributes();
    }
}
