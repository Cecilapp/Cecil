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
use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\Alignment;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Image Asset class.
 *
 * Provides methods to manipulate images, such as resizing, cropping, converting,
 * and generating data URLs.
 *
 * This class uses the Intervention Image library to handle image processing.
 * It supports GD, Imagick and libvips drivers, depending on available extensions.
 */
class Image
{
    /**
     * Returns the name of the available image driver (e.g.: "Imagick"), or null if none.
     */
    public static function getDriverName(): ?string
    {
        return self::driver()[0] ?? null;
    }

    /**
     * Returns the available driver as [name, class], or null if none.
     *
     * @return array{string, class-string}|null
     */
    private static function driver(): ?array
    {
        // Use Imagick first (fast and widely available), then libvips (fast), then GD as fallback.
        if (\extension_loaded('imagick') && class_exists('Imagick')) {
            return ['Imagick', ImagickDriver::class];
        }
        if (\extension_loaded('vips') && class_exists('Jcupitt\Vips\Config') && class_exists(VipsDriver::class)) {
            return ['Vips', VipsDriver::class];
        }
        if (\extension_loaded('gd') && \function_exists('gd_info')) {
            return ['GD', GdDriver::class];
        }

        return null;
    }

    /**
     * Create new manager instance with available driver.
     */
    private static function manager(): ImageManagerInterface
    {
        if (null !== $driver = self::driver()[1] ?? null) {
            return ImageManager::usingDriver(
                $driver,
                [
                    'autoOrientation' => true,
                    'decodeAnimation' => true,
                    'backgroundColor' => 'ffffff',
                    'strip' => true, // remove metadata
                ]
            );
        }

        throw new RuntimeException('PHP Imagick or GD extension is required, or Vips support via ext-vips/jcupitt-vips and intervention/image-driver-vips.');
    }

    /**
     * Resizes an image Asset to the given width or/and height.
     *
     * If both width and height are provided, the image is cropped to fit the dimensions.
     * If only one dimension is provided, the image is scaled proportionally.
     * The $rmAnimation parameter can be set to true to remove animations from animated images (e.g., GIFs).
     *
     * @throws RuntimeException
     */
    public static function resize(Asset $asset, ?int $width = null, ?int $height = null, int $quality = 75, bool $rmAnimation = false): string
    {
        if (self::isIco($asset)) {
            return self::resizeIco($asset, $width, $height);
        }

        try {
            $image = self::manager()->decodeBinary($asset['content']);

            if ($rmAnimation && $image->isAnimated()) {
                $image = $image->removeAnimation('25%'); // use 25% to avoid an "empty" frame
            }

            $resize = function (?int $width, ?int $height) use ($image) {
                if ($width !== null && $height !== null) {
                    return $image->cover(width: $width, height: $height, alignment: Alignment::CENTER);
                }
                if ($width !== null) {
                    return $image->scale(width: $width);
                }
                if ($height !== null) {
                    return $image->scale(height: $height);
                }
                throw new RuntimeException('Width or height must be specified.');
            };
            $image = $resize($width, $height);

            $format = Format::create($asset['ext'] ?? str_replace('image/', '', (string) $asset['subtype']));

            return (string) $image->encodeUsingFormat(
                $format,
                progressive: true,
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Asset "%s" can\'t be resized: %s.', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Resizes an ICO Asset to the given width or/and height.
     *
     * The largest icon of the ICO file is resized and returned as a single PNG-compressed icon.
     * PNG icons and 24/32 bits BMP icons are handled by any driver, other BMP icons require the Imagick extension.
     *
     * @throws RuntimeException
     */
    public static function resizeIco(Asset $asset, ?int $width = null, ?int $height = null): string
    {
        try {
            $icon = self::extractIcoLargestIcon((string) $asset['content']);
            if (str_starts_with($icon, "\x89PNG")) {
                $image = self::manager()->decodeBinary($icon);
            } elseif (null !== $png = self::dibToPng($icon)) {
                $image = self::manager()->decodeBinary($png);
            } else {
                // other BMP icon: decode a single icon ICO file with Imagick
                if (!\extension_loaded('imagick') || !class_exists('Imagick')) {
                    throw new RuntimeException('BMP icons require the PHP Imagick extension');
                }
                $image = ImageManager::usingDriver(ImagickDriver::class)->decodeBinary(self::buildIco($icon, 0, 0, 32));
            }

            if ($width !== null && $height !== null) {
                $image = $image->cover(width: $width, height: $height, alignment: Alignment::CENTER);
            } elseif ($width !== null || $height !== null) {
                $image = $image->scale(width: $width, height: $height);
            } else {
                throw new RuntimeException('Width or height must be specified');
            }

            return self::buildIco((string) $image->encodeUsingFormat(Format::PNG), $image->width(), $image->height());
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Asset "%s" can\'t be resized: %s.', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns the binary data (PNG or BMP DIB) of the largest icon of an ICO file.
     *
     * A PNG file (e.g.: renamed in ".ico") is returned as is.
     *
     * @throws RuntimeException
     */
    public static function extractIcoLargestIcon(string $data): string
    {
        if (str_starts_with($data, "\x89PNG")) {
            return $data;
        }

        $largest = self::getIcoLargestEntry($data);
        $icon = substr($data, $largest['offset'], $largest['size']);
        if (\strlen($icon) !== $largest['size'] || $largest['size'] === 0) {
            throw new RuntimeException('Invalid ICO file');
        }

        return $icon;
    }

    /**
     * Returns the size (width and height) of the largest icon of an ICO file.
     *
     * Unlike getimagesize(), which doesn't necessarily return the size of the largest icon.
     *
     * @return array{int, int}
     *
     * @throws RuntimeException
     */
    public static function getIcoSize(string $data): array
    {
        if (str_starts_with($data, "\x89PNG")) {
            if (false === $size = getimagesizefromstring($data)) {
                throw new RuntimeException('Invalid ICO file');
            }

            return [$size[0], $size[1]];
        }

        $largest = self::getIcoLargestEntry($data);

        return [$largest['width'], $largest['height']];
    }

    /**
     * Returns the directory entry of the largest icon of an ICO file.
     *
     * @return array<string, int>
     *
     * @throws RuntimeException
     */
    private static function getIcoLargestEntry(string $data): array
    {
        // ICONDIR: reserved (2 bytes), type (2 bytes, 1 = icon), count (2 bytes)
        $header = \strlen($data) >= 6 ? unpack('vreserved/vtype/vcount', $data) : false;
        if ($header === false || $header['reserved'] !== 0 || $header['type'] !== 1 || $header['count'] < 1) {
            throw new RuntimeException('Invalid ICO file');
        }

        $largest = null;
        for ($i = 0; $i < $header['count']; $i++) {
            // ICONDIRENTRY (16 bytes): width, height, colors, reserved, planes, bpp, size, offset
            $entry = substr($data, 6 + $i * 16, 16);
            if (\strlen($entry) < 16 || false === $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vsize/Voffset', $entry)) {
                throw new RuntimeException('Invalid ICO file');
            }
            // 0 means 256 pixels
            $entry['width'] = $entry['width'] ?: 256;
            $entry['height'] = $entry['height'] ?: 256;
            // the largest one, with the highest color depth
            if (
                $largest === null
                || $entry['width'] * $entry['height'] > $largest['width'] * $largest['height']
                || ($entry['width'] * $entry['height'] == $largest['width'] * $largest['height'] && $entry['bpp'] > $largest['bpp'])
            ) {
                $largest = $entry;
            }
        }

        return $largest;
    }

    /**
     * Converts a 24 or 32 bits BMP icon (DIB data of an ICO file) to PNG.
     *
     * Returns null if the BMP icon format is not supported (other color depth or compression).
     *
     * @throws RuntimeException
     */
    public static function dibToPng(string $dib): ?string
    {
        // BITMAPINFOHEADER: size, width, height (x2: XOR + AND masks), planes, bpp, compression
        $header = \strlen($dib) >= 40 ? unpack('Vsize/Vwidth/Vheight/vplanes/vbpp/Vcompression', $dib) : false;
        if ($header === false || $header['size'] < 40) {
            throw new RuntimeException('Invalid BMP icon');
        }
        if (!\in_array($header['bpp'], [24, 32], true) || $header['compression'] !== 0) {
            return null;
        }
        // height is signed: negative means top-down rows
        $topDown = $header['height'] > 0x7FFFFFFF;
        $width = $header['width'];
        $height = intdiv($topDown ? 0x100000000 - $header['height'] : $header['height'], 2);
        if ($width < 1 || $width > 1024 || $height < 1 || $height > 1024) {
            throw new RuntimeException('Invalid BMP icon');
        }

        $bytesPerPixel = $header['bpp'] / 8;
        $rowSize = (($width * $header['bpp'] + 31) >> 5) << 2; // rows are 4 bytes aligned
        $maskRowSize = (($width + 31) >> 5) << 2;
        $maskOffset = $header['size'] + $rowSize * $height;
        if (\strlen($dib) < $maskOffset) {
            throw new RuntimeException('Invalid BMP icon');
        }
        $hasMask = \strlen($dib) >= $maskOffset + $maskRowSize * $height;
        // 32 bits icons have an alpha channel, but it can be empty (transparency is then defined by the AND mask)
        $hasAlpha = false;
        if ($header['bpp'] == 32) {
            for ($i = $header['size'] + 3; $i < $maskOffset; $i += 4) {
                if ($dib[$i] !== "\0") {
                    $hasAlpha = true;
                    break;
                }
            }
        }

        // RGBA scanlines, each one prefixed by the PNG filter type (0 = none)
        $raw = '';
        for ($y = 0; $y < $height; $y++) {
            $row = $topDown ? $y : $height - 1 - $y; // BMP rows are bottom-up
            $raw .= "\0";
            for ($x = 0; $x < $width; $x++) {
                $offset = $header['size'] + $row * $rowSize + $x * $bytesPerPixel;
                $alpha = "\xff";
                if ($hasAlpha) {
                    $alpha = $dib[$offset + 3];
                } elseif ($hasMask && (\ord($dib[$maskOffset + $row * $maskRowSize + ($x >> 3)]) >> (7 - ($x & 7))) & 1) {
                    $alpha = "\0";
                }
                $raw .= $dib[$offset + 2] . $dib[$offset + 1] . $dib[$offset] . $alpha; // BGR(A) -> RGBA
            }
        }

        $chunk = fn (string $type, string $data): string => pack('N', \strlen($data)) . $type . $data . pack('N', crc32($type . $data));

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0)) // 8 bits RGBA
            . $chunk('IDAT', (string) gzcompress($raw))
            . $chunk('IEND', '');
    }

    /**
     * Builds an ICO file containing a single icon (PNG or BMP DIB data).
     */
    public static function buildIco(string $icon, int $width, int $height, int $bpp = 32): string
    {
        return pack('vvv', 0, 1, 1)
            . pack(
                'CCCCvvVV',
                $width >= 256 ? 0 : $width,
                $height >= 256 ? 0 : $height,
                0,
                0,
                1,
                $bpp,
                \strlen($icon),
                6 + 16
            )
            . $icon;
    }

    /**
     * Makes an image Asset maskable, meaning it can be used as a PWA icon.
     *
     * @throws RuntimeException
     */
    public static function maskable(Asset $asset, int $quality, int $padding): string
    {
        try {
            $source = self::manager()->decodeBinary($asset['content']);

            // creates a new image with the dominant color as background
            // and the size of the original image plus the padding
            $image = self::manager()->createImage(
                width: (int) round($asset['width'] * (1 + $padding / 100), 0),
                height: (int) round($asset['height'] * (1 + $padding / 100), 0)
            )->fill(self::getBackgroundColor($asset));
            // inserts the original image in the center
            $image->insert($source, alignment: Alignment::CENTER);

            $image->scaleDown(width: $asset['width']);

            $format = Format::create($asset['ext'] ?? str_replace('image/', '', (string) $asset['subtype']));

            return (string) $image->encodeUsingFormat(
                $format,
                progressive: true,
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to make Asset "%s" maskable: %s.', $asset['path'], $e->getMessage()));
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
            $image = self::manager()->decodeBinary($asset['content']);

            $targetFormat = Format::create($format);
            if (!$image->driver()->supports($targetFormat)) {
                throw new RuntimeException(\sprintf('Format "%s" is not supported by the image driver.', $format));
            }

            return (string) $image->encodeUsingFormat(
                $targetFormat,
                progressive: true,
                interlaced: false,
                quality: $quality
            );
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to convert "%s" to %s: %s.', $asset['path'], $format, $e->getMessage()));
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
            $image = self::manager()->decodeBinary($asset['content']);

            $format = Format::create($asset['ext'] ?? str_replace('image/', '', (string) $asset['subtype']));

            return (string) $image->encodeUsingFormat($format, quality: $quality)->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get Data URL of "%s": %s.', $asset['path'], $e->getMessage()));
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
            $image = self::manager()->decodeBinary(self::resize($asset, 100, 50));
            // @phpstan-ignore method.notFound
            $palette = $image->colors()->dominant();

            return $palette->first()->toString();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get dominant color of "%s": %s.', $asset['_path'], $e->getMessage()));
        }
    }

    /**
     * Returns the background RGB color of an image asset.
     *
     * @throws RuntimeException
     */
    public static function getBackgroundColor(Asset $asset): string
    {
        try {
            $image = self::manager()->decodeBinary(self::resize($asset, 100, 50));

            return $image->colorAt(0, 0)->toString();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to get background color of "%s": %s.', $asset['path'], $e->getMessage()));
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
            $image = self::manager()->decodeBinary(self::resize($asset, 100, 50));

            return (string) $image->blur(50)->encode()->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to create LQIP of "%s": %s.', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Builds the asset path for a dark color-scheme image variant.
     */
    public static function buildDarkAssetPath(string $assetPath, string $darkSuffix): string
    {
        $pathInfo = pathinfo($assetPath);
        // on Windows, `dirname` of a root file is "\"
        $dirname = str_replace('\\', '/', $pathInfo['dirname']);
        $extension = empty($pathInfo['extension']) ? '' : '.' . $pathInfo['extension'];

        return rtrim($dirname, '/') . '/' . $pathInfo['filename'] . $darkSuffix . $extension;
    }

    /**
     * Builds dark color-scheme source attributes for an image.
     *
     * @param array<string> $formats
     * @param array{
     *   responsive?: mixed,
     *   widths?: array<int>,
     *   densities?: array<float|int>,
     *   sizes?: ?string,
     *   width1x?: ?int,
     *   assetOptions?: array<mixed>,
     *   url?: ?callable
     * } $options
     *
     * @return array<array<string, string>>
     */
    public static function buildDarkSourceAttributes(
        Builder $builder,
        Asset $asset,
        string $darkSuffix,
        array $formats,
        array $options = []
    ): array {
        if (empty($darkSuffix) or $asset['url'] !== null) {
            return [];
        }

        $responsive = $options['responsive'] ?? false;
        $widths = $options['widths'] ?? [];
        $densities = $options['densities'] ?? [];
        $sizes = $options['sizes'] ?? null;
        $width1x = $options['width1x'] ?? null;
        $assetOptions = $options['assetOptions'] ?? [];
        $url = $options['url'] ?? null;
        $darkAssetPath = self::buildDarkAssetPath($asset['_path'], $darkSuffix);
        $assetDark = new Asset($builder, $darkAssetPath, array_merge(['ignore_missing' => true], $assetOptions));
        if ($assetDark->isMissing()) {
            $builder->getLogger()->warning(\sprintf(
                'Dark variant "%s" not found for image "%s".',
                $darkAssetPath,
                $asset['_path']
            ));

            return [];
        }
        $darkSources = [];
        foreach ($formats as $format) {
            try {
                $assetDarkConverted = $assetDark->convert($format);
                if ($responsive === true || $responsive === 'width') {
                    $darkSrcset = !empty($widths) ? self::buildHtmlSrcsetW($assetDarkConverted, $widths, false, $url) : '';
                } elseif ($responsive === 'density') {
                    $darkSrcset = !empty($densities)
                        ? self::buildHtmlSrcsetX($assetDarkConverted, $width1x ?? $assetDark['width'], $densities, $url)
                        : '';
                } else {
                    $darkSrcset = '';
                }
                $darkSourceAttributes = [
                    'media'  => '(prefers-color-scheme: dark)',
                    'type'   => "image/$format",
                    'srcset' => empty($darkSrcset) ? self::url($assetDarkConverted, $url) : $darkSrcset,
                ];
                if (!empty($sizes)) {
                    $darkSourceAttributes['sizes'] = $sizes;
                }
                $darkSources[] = $darkSourceAttributes;
            } catch (\Exception $e) {
                $builder->getLogger()->warning($e->getMessage());
            }
        }
        $darkFallbackSrcset = self::url($assetDark, $url);
        if (($responsive === true || $responsive === 'width') && !empty($widths)) {
            try {
                $darkResponsiveSrcset = self::buildHtmlSrcsetW($assetDark, $widths, false, $url);
                if (!empty($darkResponsiveSrcset)) {
                    $darkFallbackSrcset = $darkResponsiveSrcset;
                }
            } catch (\Exception $e) {
                $builder->getLogger()->warning($e->getMessage());
            }
        }
        $darkFallbackSourceAttributes = [
            'media'  => '(prefers-color-scheme: dark)',
            'srcset' => $darkFallbackSrcset,
        ];
        if (!empty($sizes)) {
            $darkFallbackSourceAttributes['sizes'] = $sizes;
        }
        $darkSources[] = $darkFallbackSourceAttributes;

        return $darkSources;
    }

    /**
     * Build the `srcset` HTML attribute for responsive images, based on widths.
     * e.g.: `srcset="/img-480.jpg 480w, /img-800.jpg 800w"`.
     *
     * @param array         $widths   An array of widths to include in the `srcset`
     * @param bool          $notEmpty If true the source image is always added to the `srcset`
     * @param callable|null $url      Optional URL builder, called with each Asset (e.g.: to handle base URL)
     *
     * @throws RuntimeException
     */
    public static function buildHtmlSrcsetW(Asset $asset, array $widths, $notEmpty = false, ?callable $url = null): string
    {
        if (!self::isImage($asset)) {
            throw new RuntimeException(\sprintf('Unable to build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = [];
        $widthMax = 0;
        sort($widths, SORT_NUMERIC);
        $widths = array_reverse($widths);
        foreach ($widths as $width) {
            if ($asset['width'] < $width) {
                continue;
            }
            $img = $asset->resize($width);
            array_unshift($srcset, \sprintf('%s %sw', self::url($img, $url), $width));
            $widthMax = $width;
        }
        // adds source image
        if ((!empty($srcset) || $notEmpty) && ($asset['width'] < max($widths) && $asset['width'] != $widthMax)) {
            $srcset[] = \sprintf('%s %sw', self::url($asset, $url), $asset['width']);
        }

        return implode(', ', $srcset);
    }

    /**
     * Alias of buildHtmlSrcsetW for backward compatibility.
     */
    public static function buildHtmlSrcset(Asset $asset, array $widths, $notEmpty = false, ?callable $url = null): string
    {
        return self::buildHtmlSrcsetW($asset, $widths, $notEmpty, $url);
    }

    /**
     * Build the `srcset` HTML attribute for responsive images, based on pixel ratios.
     * e.g.: `srcset="/img-1x.jpg 1.0x, /img-2x.jpg 2.0x"`.
     *
     * @param int           $width1x The width of the 1x image
     * @param array         $ratios  An array of pixel ratios to include in the `srcset`
     * @param callable|null $url     Optional URL builder, called with each Asset (e.g.: to handle base URL)
     *
     * @throws RuntimeException
     */
    public static function buildHtmlSrcsetX(Asset $asset, int $width1x, array $ratios, ?callable $url = null): string
    {
        if (!self::isImage($asset)) {
            throw new RuntimeException(\sprintf('Unable to build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = [];
        sort($ratios, SORT_NUMERIC);
        $ratios = array_reverse($ratios);
        foreach ($ratios as $ratio) {
            if ($ratio <= 1) {
                continue;
            }
            $width = (int) round($width1x * $ratio, 0);
            if ($asset['width'] < $width) {
                continue;
            }
            $img = $asset->resize($width);
            array_unshift($srcset, \sprintf('%s %dx', self::url($img, $url), $ratio));
        }
        // adds 1x image
        array_unshift($srcset, \sprintf('%s 1x', self::url($asset->resize($width1x), $url)));

        return implode(', ', $srcset);
    }

    /**
     * Returns the URL of an Asset, built with the URL builder if provided.
     */
    private static function url(Asset $asset, ?callable $url = null): string
    {
        return $url !== null ? (string) $url($asset) : (string) $asset;
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
