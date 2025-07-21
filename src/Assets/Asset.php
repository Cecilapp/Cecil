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

namespace Cecil\Assets;

use Cecil\Assets\Image\Optimizer;
use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Url;
use Cecil\Util;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use wapmorgan\Mp3Info\Mp3Info;

/**
 * Asset class.
 *
 * Represents an asset (file) in the Cecil project.
 * Handles file locating, content reading, compiling, minifying, fingerprinting,
 * resizing images, and more.
 */
class Asset implements \ArrayAccess
{
    public const IMAGE_THUMB = 'thumbnails';

    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    /** @var array */
    protected $data = [];

    /** @var array Cache tags */
    protected $cacheTags = [];

    /**
     * Creates an Asset from a file path, an array of files path or an URL.
     * Options:
     * [
     *     'filename' => <string>,
     *     'leading_slash' => <bool>
     *     'ignore_missing' => <bool>,
     *     'fingerprint' => <bool>,
     *     'minify' => <bool>,
     *     'optimize' => <bool>,
     *     'fallback' => <string>,
     *     'useragent' => <string>,
     * ]
     *
     * @param Builder      $builder
     * @param string|array $paths
     * @param array|null   $options
     *
     * @throws RuntimeException
     */
    public function __construct(Builder $builder, string|array $paths, array|null $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $paths = \is_array($paths) ? $paths : [$paths];
        // checks path(s)
        array_walk($paths, function ($path) {
            // must be a string
            if (!\is_string($path)) {
                throw new RuntimeException(\sprintf('The path of an asset must be a string ("%s" given).', \gettype($path)));
            }
            // can't be empty
            if (empty($path)) {
                throw new RuntimeException('The path of an asset can\'t be empty.');
            }
            // can't be relative
            if (substr($path, 0, 2) == '..') {
                throw new RuntimeException(\sprintf('The path of asset "%s" is wrong: it must be directly relative to `assets` or `static` directory, or a remote URL.', $path));
            }
        });
        $this->data = [
            'file'     => '',    // absolute file path
            'files'    => [],    // array of absolute files path
            'missing'  => false, // if file not found but missing allowed: 'missing' is true
            '_path'    => '',    // original path
            'path'     => '',    // public path
            'url'      => null,  // URL if it's a remote file
            'ext'      => '',    // file extension
            'type'     => '',    // file type (e.g.: image, audio, video, etc.)
            'subtype'  => '',    // file media type (e.g.: image/png, audio/mp3, etc.)
            'size'     => 0,     // file size (in bytes)
            'width'    => 0,     // image width (in pixels)
            'height'   => 0,     // image height (in pixels)
            'exif'     => [],    // image exif data
            'content'  => '',    // file content
            'hash'     => '',    // file content hash (md5)
        ];

        // handles options
        $options = array_merge(
            [
                'filename'       => '',
                'leading_slash'  => true,
                'ignore_missing' => false,
                'fingerprint'    => $this->config->isEnabled('assets.fingerprint'),
                'minify'         => $this->config->isEnabled('assets.minify'),
                'optimize'       => $this->config->isEnabled('assets.images.optimize'),
                'fallback'       => '',
                'useragent'      => (string) $this->config->get('assets.remote.useragent.default'),
            ],
            \is_array($options) ? $options : []
        );

        // cache
        $cache = new Cache($this->builder, 'assets');
        $locateCacheKey = \sprintf('%s_locate__%s__%s', $options['filename'] ?: implode('_', $paths), $this->builder->getBuildId(), $this->builder->getVersion());

        // locate file(s) and get content
        if (!$cache->has($locateCacheKey)) {
            $pathsCount = \count($paths);
            for ($i = 0; $i < $pathsCount; $i++) {
                try {
                    $this->data['missing'] = false;
                    $locate = $this->locateFile($paths[$i], $options['fallback'], $options['useragent']);
                    $file = $locate['file'];
                    $path = $locate['path'];
                    $type = Util\File::getMediaType($file)[0];
                    if ($i > 0) { // bundle
                        if ($type != $this->data['type']) {
                            throw new RuntimeException(\sprintf('Asset bundle type error (%s != %s).', $type, $this->data['type']));
                        }
                    }
                    $this->data['file'] = $file;
                    $this->data['files'][] = $file;
                    $this->data['path'] = $path;
                    $this->data['url'] = Util\File::isRemote($paths[$i]) ? $paths[$i] : null;
                    $this->data['ext'] = Util\File::getExtension($file);
                    $this->data['type'] = $type;
                    $this->data['subtype'] = Util\File::getMediaType($file)[1];
                    $this->data['size'] += filesize($file);
                    $this->data['content'] .= Util\File::fileGetContents($file);
                    $this->data['hash'] = hash('md5', $this->data['content']);
                    // bundle default filename
                    $filename = $options['filename'];
                    if ($pathsCount > 1 && empty($filename)) {
                        switch ($this->data['ext']) {
                            case 'scss':
                            case 'css':
                                $filename = 'styles.css';
                                break;
                            case 'js':
                                $filename = 'scripts.js';
                                break;
                            default:
                                throw new RuntimeException(\sprintf('Asset bundle supports %s files only.', '.scss, .css and .js'));
                        }
                    }
                    // apply bundle filename to path
                    if (!empty($filename)) {
                        $this->data['path'] = $filename;
                    }
                    // add leading slash
                    if ($options['leading_slash']) {
                        $this->data['path'] = '/' . ltrim($this->data['path'], '/');
                    }
                    $this->data['_path'] = $this->data['path'];
                } catch (RuntimeException $e) {
                    if ($options['ignore_missing']) {
                        $this->data['missing'] = true;
                        continue;
                    }
                    throw new RuntimeException(
                        \sprintf('Can\'t handle asset "%s".', $paths[$i]),
                        previous: $e
                    );
                }
            }
            $cache->set($locateCacheKey, $this->data);
        }
        $this->data = $cache->get($locateCacheKey);

        // missing
        if ($this->data['missing']) {
            return;
        }

        // cache
        $cache = new Cache($this->builder, 'assets');
        // create cache tags from options
        $this->cacheTags = $options;
        // remove some cache tags
        unset($this->cacheTags['optimize'], $this->cacheTags['ignore_missing'], $this->cacheTags['fallback'], $this->cacheTags['useragent']);
        // optimize images?
        $optimize = false;
        if ($options['optimize'] && $this->data['type'] == 'image' && !$this->isImageInCdn()) {
            $optimize = true;
            $quality = (int) $this->config->get('assets.images.quality');
            $this->cacheTags['quality'] = $quality;
        }
        $cacheKey = $cache->createKeyFromAsset($this, $this->cacheTags);
        if (!$cache->has($cacheKey)) {
            // image: width, height and exif
            if ($this->data['type'] == 'image') {
                $this->data['width'] = $this->getWidth();
                $this->data['height'] = $this->getHeight();
                if ($this->data['subtype'] == 'image/jpeg') {
                    $this->data['exif'] = Util\File::readExif($this->data['file']);
                }
            }
            // fingerprinting
            if ($options['fingerprint']) {
                $this->doFingerprint();
            }
            // compiling Sass files
            $this->doCompile();
            // minifying (CSS and JavScript files)
            if ($options['minify']) {
                $this->doMinify();
            }
            $cache->set($cacheKey, $this->data, $this->config->get('cache.assets.ttl'));
            $this->builder->getLogger()->debug(\sprintf('Asset cached: "%s"', $this->data['path']));
            // optimizing images files (in cache directory)
            if ($optimize) {
                $this->optimize($cache->getContentFilePathname($this->data['path']), $this->data['path'], $quality);
            }
        }
        $this->data = $cache->get($cacheKey);
    }

    /**
     * Returns path.
     */
    public function __toString(): string
    {
        $this->save();

        if ($this->isImageInCdn()) {
            return $this->buildImageCdnUrl();
        }

        if ($this->builder->getConfig()->isEnabled('canonicalurl')) {
            return (string) new Url($this->builder, $this->data['path'], ['canonical' => true]);
        }

        return $this->data['path'];
    }

    /**
     * Compiles a SCSS + cache.
     *
     * @throws RuntimeException
     */
    public function compile(): self
    {
        $this->cacheTags['compile'] = true;
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this, $this->cacheTags);
        if (!$cache->has($cacheKey)) {
            $this->doCompile();
            $cache->set($cacheKey, $this->data, $this->config->get('cache.assets.ttl'));
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Minifying a CSS or a JS.
     */
    public function minify(): self
    {
        $this->cacheTags['minify'] = true;
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this, $this->cacheTags);
        if (!$cache->has($cacheKey)) {
            $this->doMinify();
            $cache->set($cacheKey, $this->data, $this->config->get('cache.assets.ttl'));
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Add hash to the file name + cache.
     */
    public function fingerprint(): self
    {
        $this->cacheTags['fingerprint'] = true;
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this, $this->cacheTags);
        if (!$cache->has($cacheKey)) {
            $this->doFingerprint();
            $cache->set($cacheKey, $this->data, $this->config->get('cache.assets.ttl'));
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Scales down an image to a new $width.
     *
     * @throws RuntimeException
     */
    public function resize(int $width): self
    {
        $this->checkImage();

        // if the image is already smaller than the requested width, return it
        if ($width >= $this->data['width']) {
            return $this;
        }

        $assetResized = clone $this;
        $assetResized->data['width'] = $width;

        if ($this->isImageInCdn()) {
            $assetResized->data['height'] = round($this->data['height'] / ($this->data['width'] / $width));

            return $assetResized; // returns asset with the new dimensions only: CDN do the rest of the job
        }

        $quality = (int) $this->config->get('assets.images.quality');

        $cache = new Cache($this->builder, 'assets');
        $assetResized->cacheTags['quality'] = $quality;
        $assetResized->cacheTags['width'] = $width;
        $cacheKey = $cache->createKeyFromAsset($assetResized, $assetResized->cacheTags);
        if (!$cache->has($cacheKey)) {
            $assetResized->data['content'] = Image::resize($assetResized, $width, $quality);
            $assetResized->data['path'] = '/' . Util::joinPath(
                (string) $this->config->get('assets.target'),
                self::IMAGE_THUMB,
                (string) $width,
                $assetResized->data['path']
            );
            $assetResized->data['path'] = $this->deduplicateThumbPath($assetResized->data['path']);
            $assetResized->data['height'] = $assetResized->getHeight();
            $assetResized->data['size'] = \strlen($assetResized->data['content']);

            $cache->set($cacheKey, $assetResized->data, $this->config->get('cache.assets.ttl'));
            $this->builder->getLogger()->debug(\sprintf('Asset resized: "%s" (%sx)', $assetResized->data['path'], $width));
        }
        $assetResized->data = $cache->get($cacheKey);

        return $assetResized;
    }

    /**
     * Crops the image to the specified width and height, keeping the specified position.
     *
     * @throws RuntimeException
     */
    public function cover(int $width, int $height): self
    {
        $this->checkImage();

        $assetResized = clone $this;
        $assetResized->data['width'] = $width;
        $assetResized->data['height'] = $height;

        $quality = (int) $this->config->get('assets.images.quality');

        $cache = new Cache($this->builder, 'assets');
        $assetResized->cacheTags['quality'] = $quality;
        $assetResized->cacheTags['width'] = $width;
        $assetResized->cacheTags['height'] = $height;
        $cacheKey = $cache->createKeyFromAsset($assetResized, $assetResized->cacheTags);
        if (!$cache->has($cacheKey)) {
            $assetResized->data['content'] = Image::cover($assetResized, $width, $height, $quality);
            $assetResized->data['path'] = '/' . Util::joinPath(
                (string) $this->config->get('assets.target'),
                self::IMAGE_THUMB,
                (string) $width . 'x' . (string) $height,
                $assetResized->data['path']
            );
            $assetResized->data['path'] = $this->deduplicateThumbPath($assetResized->data['path']);
            $assetResized->data['size'] = \strlen($assetResized->data['content']);

            $cache->set($cacheKey, $assetResized->data, $this->config->get('cache.assets.ttl'));
            $this->builder->getLogger()->debug(\sprintf('Asset resized: "%s" (%sx%s)', $assetResized->data['path'], $width, $height));
        }
        $assetResized->data = $cache->get($cacheKey);

        return $assetResized;
    }

    /**
     * Creates a maskable image (with a padding = 20%).
     *
     * @throws RuntimeException
     */
    public function maskable(?int $padding = null): self
    {
        $this->checkImage();

        if ($padding === null) {
            $padding = 20; // default padding
        }

        $assetMaskable = clone $this;

        $quality = (int) $this->config->get('assets.images.quality');

        $cache = new Cache($this->builder, 'assets');
        $assetMaskable->cacheTags['maskable'] = true;
        $cacheKey = $cache->createKeyFromAsset($assetMaskable, $assetMaskable->cacheTags);
        if (!$cache->has($cacheKey)) {
            $assetMaskable->data['content'] = Image::maskable($assetMaskable, $quality, $padding);
            $assetMaskable->data['path'] = '/' . Util::joinPath(
                (string) $this->config->get('assets.target'),
                'maskable',
                $assetMaskable->data['path']
            );
            $assetMaskable->data['size'] = \strlen($assetMaskable->data['content']);

            $cache->set($cacheKey, $assetMaskable->data, $this->config->get('cache.assets.ttl'));
            $this->builder->getLogger()->debug(\sprintf('Asset maskabled: "%s"', $assetMaskable->data['path']));
        }
        $assetMaskable->data = $cache->get($cacheKey);

        return $assetMaskable;
    }

    /**
     * Converts an image asset to $format format.
     *
     * @throws RuntimeException
     */
    public function convert(string $format, ?int $quality = null): self
    {
        if ($this->data['type'] != 'image') {
            throw new RuntimeException(\sprintf('Not able to convert "%s" (%s) to %s: not an image.', $this->data['path'], $this->data['type'], $format));
        }

        if ($quality === null) {
            $quality = (int) $this->config->get('assets.images.quality');
        }

        $asset = clone $this;
        $asset['ext'] = $format;
        $asset->data['subtype'] = "image/$format";

        if ($this->isImageInCdn()) {
            return $asset; // returns the asset with the new extension only: CDN do the rest of the job
        }

        $cache = new Cache($this->builder, 'assets');
        $this->cacheTags['quality'] = $quality;
        if ($this->data['width']) {
            $this->cacheTags['width'] = $this->data['width'];
        }
        $cacheKey = $cache->createKeyFromAsset($asset, $this->cacheTags);
        if (!$cache->has($cacheKey)) {
            $asset->data['content'] = Image::convert($asset, $format, $quality);
            $asset->data['path'] = preg_replace('/\.' . $this->data['ext'] . '$/m', ".$format", $this->data['path']);
            $asset->data['size'] = \strlen($asset->data['content']);
            $cache->set($cacheKey, $asset->data, $this->config->get('cache.assets.ttl'));
            $this->builder->getLogger()->debug(\sprintf('Asset converted: "%s" (%s -> %s)', $asset->data['path'], $this->data['ext'], $format));
        }
        $asset->data = $cache->get($cacheKey);

        return $asset;
    }

    /**
     * Converts an image asset to WebP format.
     *
     * @throws RuntimeException
     */
    public function webp(?int $quality = null): self
    {
        return $this->convert('webp', $quality);
    }

    /**
     * Converts an image asset to AVIF format.
     *
     * @throws RuntimeException
     */
    public function avif(?int $quality = null): self
    {
        return $this->convert('avif', $quality);
    }

    /**
     * Implements \ArrayAccess.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if (!\is_null($offset)) {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Implements \ArrayAccess.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Hashing content of an asset with the specified algo, sha384 by default.
     * Used for SRI (Subresource Integrity).
     *
     * @see https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity
     */
    public function getIntegrity(string $algo = 'sha384'): string
    {
        return \sprintf('%s-%s', $algo, base64_encode(hash($algo, $this->data['content'], true)));
    }

    /**
     * Returns MP3 file infos.
     *
     * @see https://github.com/wapmorgan/Mp3Info
     */
    public function getAudio(): Mp3Info
    {
        if ($this->data['type'] !== 'audio') {
            throw new RuntimeException(\sprintf('Not able to get audio infos of "%s".', $this->data['path']));
        }

        return new Mp3Info($this->data['file']);
    }

    /**
     * Returns MP4 file infos.
     *
     * @see https://github.com/clwu88/php-read-mp4info
     */
    public function getVideo(): array
    {
        if ($this->data['type'] !== 'video') {
            throw new RuntimeException(\sprintf('Not able to get video infos of "%s".', $this->data['path']));
        }

        return (array) \Clwu\Mp4::getInfo($this->data['file']);
    }

    /**
     * Returns the Data URL (encoded in Base64).
     *
     * @throws RuntimeException
     */
    public function dataurl(): string
    {
        if ($this->data['type'] == 'image' && !Image::isSVG($this)) {
            return Image::getDataUrl($this, (int) $this->config->get('assets.images.quality'));
        }

        return \sprintf('data:%s;base64,%s', $this->data['subtype'], base64_encode($this->data['content']));
    }

    /**
     * Adds asset path to the list of assets to save.
     *
     * @throws RuntimeException
     */
    public function save(): void
    {
        if ($this->data['missing']) {
            return;
        }

        $cache = new Cache($this->builder, 'assets');
        if (empty($this->data['path']) || !Util\File::getFS()->exists($cache->getContentFilePathname($this->data['path']))) {
            throw new RuntimeException(
                \sprintf('Can\'t add "%s" to assets list. Please clear cache and retry.', $this->data['path'])
            );
        }

        $this->builder->addAsset($this->data['path']);
    }

    /**
     * Is the asset an image and is it in CDN?
     */
    public function isImageInCdn(): bool
    {
        if (
            $this->data['type'] == 'image'
            && $this->config->isEnabled('assets.images.cdn')
            && $this->data['ext'] != 'ico'
            && (Image::isSVG($this) && $this->config->isEnabled('assets.images.cdn.svg'))
        ) {
            return true;
        }
        // handle remote image?
        if ($this->data['url'] !== null && $this->config->isEnabled('assets.images.cdn.remote')) {
            return true;
        }

        return false;
    }

    /**
     * Builds a relative path from a URL.
     * Used for remote files.
     */
    public static function buildPathFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

        // Google Fonts hack
        if (Util\Str::endsWith($path, '/css') || Util\Str::endsWith($path, '/css2')) {
            $ext = 'css';
        }

        return Page::slugify(\sprintf('%s%s%s%s', $host, self::sanitize($path), $query ? "-$query" : '', $query && $ext ? ".$ext" : ''));
    }

    /**
     * Replaces some characters by '_'.
     */
    public static function sanitize(string $string): string
    {
        return str_replace(['<', '>', ':', '"', '\\', '|', '?', '*'], '_', $string);
    }

    /**
     * Compiles a SCSS.
     *
     * @throws RuntimeException
     */
    protected function doCompile(): self
    {
        // abort if not a SCSS file
        if ($this->data['ext'] != 'scss') {
            return $this;
        }
        $scssPhp = new Compiler();
        // import paths
        $importDir = [];
        $importDir[] = Util::joinPath($this->config->getStaticPath());
        $importDir[] = Util::joinPath($this->config->getAssetsPath());
        $scssDir = (array) $this->config->get('assets.compile.import');
        $themes = $this->config->getTheme() ?? [];
        foreach ($scssDir as $dir) {
            $importDir[] = Util::joinPath($this->config->getStaticPath(), $dir);
            $importDir[] = Util::joinPath($this->config->getAssetsPath(), $dir);
            $importDir[] = Util::joinPath(\dirname($this->data['file']), $dir);
            foreach ($themes as $theme) {
                $importDir[] = Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir"));
                $importDir[] = Util::joinPath($this->config->getThemeDirPath($theme, "assets/$dir"));
            }
        }
        $scssPhp->setQuietDeps(true);
        $scssPhp->setImportPaths(array_unique($importDir));
        // adds source map
        if ($this->builder->isDebug() && $this->config->isEnabled('assets.compile.sourcemap')) {
            $importDir = [];
            $assetDir = (string) $this->config->get('assets.dir');
            $assetDirPos = strrpos($this->data['file'], DIRECTORY_SEPARATOR . $assetDir . DIRECTORY_SEPARATOR);
            $fileRelPath = substr($this->data['file'], $assetDirPos + 8);
            $filePath = Util::joinFile($this->config->getOutputPath(), $fileRelPath);
            $importDir[] = \dirname($filePath);
            foreach ($scssDir as $dir) {
                $importDir[] = Util::joinFile($this->config->getOutputPath(), $dir);
            }
            $scssPhp->setImportPaths(array_unique($importDir));
            $scssPhp->setSourceMap(Compiler::SOURCE_MAP_INLINE);
            $scssPhp->setSourceMapOptions([
                'sourceMapBasepath' => Util::joinPath($this->config->getOutputPath()),
                'sourceRoot'        => '/',
            ]);
        }
        // defines output style
        $outputStyles = ['expanded', 'compressed'];
        $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
        if (!\in_array($outputStyle, $outputStyles)) {
            throw new ConfigException(\sprintf('"%s" value must be "%s".', 'assets.compile.style', implode('" or "', $outputStyles)));
        }
        $scssPhp->setOutputStyle($outputStyle == 'compressed' ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
        // set variables
        $variables = $this->config->get('assets.compile.variables');
        if (!empty($variables)) {
            $variables = array_map('ScssPhp\ScssPhp\ValueConverter::parseValue', $variables);
            $scssPhp->replaceVariables($variables);
        }
        // debug
        if ($this->builder->isDebug()) {
            $scssPhp->setQuietDeps(false);
            $this->builder->getLogger()->debug(\sprintf("SCSS compiler imported paths:\n%s", Util\Str::arrayToList(array_unique($importDir))));
        }
        // update data
        $this->data['path'] = preg_replace('/sass|scss/m', 'css', $this->data['path']);
        $this->data['ext'] = 'css';
        $this->data['type'] = 'text';
        $this->data['subtype'] = 'text/css';
        $this->data['content'] = $scssPhp->compileString($this->data['content'])->getCss();
        $this->data['size'] = \strlen($this->data['content']);

        $this->builder->getLogger()->debug(\sprintf('Asset compiled: "%s"', $this->data['path']));

        return $this;
    }

    /**
     * Minifying a CSS or a JS + cache.
     *
     * @throws RuntimeException
     */
    protected function doMinify(): self
    {
        // in debug mode: disable minify to preserve inline source map
        if ($this->builder->isDebug() && $this->config->isEnabled('assets.compile.sourcemap')) {
            return $this;
        }
        // abord if not a CSS or JS file
        if ($this->data['ext'] != 'css' && $this->data['ext'] != 'js') {
            return $this;
        }
        // abort if already minified
        if (substr($this->data['path'], -8) == '.min.css' || substr($this->data['path'], -7) == '.min.js') {
            return $this;
        }
        // compile SCSS files
        if ($this->data['ext'] == 'scss') {
            $this->compile();
        }
        switch ($this->data['ext']) {
            case 'css':
                $minifier = new Minify\CSS($this->data['content']);
                break;
            case 'js':
                $minifier = new Minify\JS($this->data['content']);
                break;
            default:
                throw new RuntimeException(\sprintf('Not able to minify "%s".', $this->data['path']));
        }
        $this->data['content'] = $minifier->minify();
        $this->data['size'] = \strlen($this->data['content']);

        $this->builder->getLogger()->debug(\sprintf('Asset minified: "%s"', $this->data['path']));

        return $this;
    }

    /**
     * Add hash to the file name.
     */
    protected function doFingerprint(): self
    {
        $hash = hash('md5', $this->data['content']);
        $this->data['path'] = preg_replace(
            '/\.' . $this->data['ext'] . '$/m',
            ".$hash." . $this->data['ext'],
            $this->data['path']
        );
        $this->builder->getLogger()->debug(\sprintf('Asset fingerprinted: "%s"', $this->data['path']));

        return $this;
    }

    /**
     * Returns local file path and updated path, or throw an exception.
     * If $fallback path is set, it will be used if the remote file is not found.
     *
     * Try to locate the file in:
     *   (1. remote file)
     *   1. assets
     *   2. themes/<theme>/assets
     *   3. static
     *   4. themes/<theme>/static
     *
     * @throws RuntimeException
     */
    private function locateFile(string $path, ?string $fallback = null, ?string $userAgent = null): array
    {
        // remote file
        if (Util\File::isRemote($path)) {
            try {
                $content = $this->getRemoteFileContent($path, $userAgent);
                $path = self::buildPathFromUrl($path);
                $cache = new Cache($this->builder, 'assets/remote');
                if (!$cache->has($path)) {
                    $cache->set($path, [
                        'content' => $content,
                        'path'    => $path,
                    ], $this->config->get('cache.assets.remote.ttl'));
                }
                return [
                    'file' => $cache->getContentFilePathname($path),
                    'path' => $path,
                ];
            } catch (RuntimeException $e) {
                if (empty($fallback)) {
                    throw new RuntimeException($e->getMessage());
                }
                $path = $fallback;
            }
        }

        // checks in assets/
        $file = Util::joinFile($this->config->getAssetsPath(), $path);
        if (Util\File::getFS()->exists($file)) {
            return [
                'file' => $file,
                'path' => $path,
            ];
        }

        // checks in each themes/<theme>/assets/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            $file = Util::joinFile($this->config->getThemeDirPath($theme, 'assets'), $path);
            if (Util\File::getFS()->exists($file)) {
                return [
                    'file' => $file,
                    'path' => $path,
                ];
            }
        }

        // checks in static/
        $file = Util::joinFile($this->config->getStaticPath(), $path);
        if (Util\File::getFS()->exists($file)) {
            return [
                'file' => $file,
                'path' => $path,
            ];
        }

        // checks in each themes/<theme>/static/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            $file = Util::joinFile($this->config->getThemeDirPath($theme, 'static'), $path);
            if (Util\File::getFS()->exists($file)) {
                return [
                    'file' => $file,
                    'path' => $path,
                ];
            }
        }

        throw new RuntimeException(\sprintf('Can\'t locate file "%s".', $path));
    }

    /**
     * Try to get remote file content.
     * Returns file content or throw an exception.
     *
     * @throws RuntimeException
     */
    private function getRemoteFileContent(string $path, ?string $userAgent = null): string
    {
        if (!Util\File::isRemoteExists($path)) {
            throw new RuntimeException(\sprintf('Can\'t get remote file "%s".', $path));
        }
        if (false === $content = Util\File::fileGetContents($path, $userAgent)) {
            throw new RuntimeException(\sprintf('Can\'t get content of remote file "%s".', $path));
        }
        if (\strlen($content) <= 1) {
            throw new RuntimeException(\sprintf('Remote file "%s" is empty.', $path));
        }

        return $content;
    }

    /**
     * Optimizing $filepath image.
     * Returns the new file size.
     */
    private function optimize(string $filepath, string $path, int $quality): int
    {
        $message = \sprintf('Asset not optimized: "%s"', $path);
        $sizeBefore = filesize($filepath);
        Optimizer::create($quality)->optimize($filepath);
        $sizeAfter = filesize($filepath);
        if ($sizeAfter < $sizeBefore) {
            $message = \sprintf(
                'Asset optimized: "%s" (%s Ko -> %s Ko)',
                $path,
                ceil($sizeBefore / 1000),
                ceil($sizeAfter / 1000)
            );
        }
        $this->builder->getLogger()->debug($message);

        return $sizeAfter;
    }

    /**
     * Returns the width of an image/SVG.
     *
     * @throws RuntimeException
     */
    private function getWidth(): int
    {
        if ($this->data['type'] != 'image') {
            return 0;
        }
        if (Image::isSVG($this) && false !== $svg = Image::getSvgAttributes($this)) {
            return (int) $svg->width;
        }
        if (false === $size = $this->getImageSize()) {
            throw new RuntimeException(\sprintf('Not able to get width of "%s".', $this->data['path']));
        }

        return $size[0];
    }

    /**
     * Returns the height of an image/SVG.
     *
     * @throws RuntimeException
     */
    private function getHeight(): int
    {
        if ($this->data['type'] != 'image') {
            return 0;
        }
        if (Image::isSVG($this) && false !== $svg = Image::getSvgAttributes($this)) {
            return (int) $svg->height;
        }
        if (false === $size = $this->getImageSize()) {
            throw new RuntimeException(\sprintf('Not able to get height of "%s".', $this->data['path']));
        }

        return $size[1];
    }

    /**
     * Returns image size informations.
     *
     * @see https://www.php.net/manual/function.getimagesize.php
     *
     * @return array|false
     */
    private function getImageSize()
    {
        if (!$this->data['type'] == 'image') {
            return false;
        }

        try {
            if (false === $size = getimagesizefromstring($this->data['content'])) {
                return false;
            }
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Handling asset "%s" failed: "%s"', $this->data['path'], $e->getMessage()));
        }

        return $size;
    }

    /**
     * Builds CDN image URL.
     */
    private function buildImageCdnUrl(): string
    {
        return str_replace(
            [
                '%account%',
                '%image_url%',
                '%width%',
                '%quality%',
                '%format%',
            ],
            [
                $this->config->get('assets.images.cdn.account') ?? '',
                ltrim($this->data['url'] ?? (string) new Url($this->builder, $this->data['path'], ['canonical' => $this->config->get('assets.images.cdn.canonical') ?? true]), '/'),
                $this->data['width'],
                (int) $this->config->get('assets.images.quality'),
                $this->data['ext'],
            ],
            (string) $this->config->get('assets.images.cdn.url')
        );
    }

    /**
     * Checks if the asset is not missing and is typed as an image.
     *
     * @throws RuntimeException
     */
    private function checkImage(): void
    {
        if ($this->data['missing']) {
            throw new RuntimeException(\sprintf('Not able to resize "%s": file not found.', $this->data['path']));
        }
        if ($this->data['type'] != 'image') {
            throw new RuntimeException(\sprintf('Not able to resize "%s": not an image.', $this->data['path']));
        }
    }

    /**
     * Remove redondant '/thumbnails/<width>/' in the path.
     */
    private function deduplicateThumbPath(string $path): string
    {
        // https://regex101.com/r/rDRWnL/1
        $pattern = '/(' . self::IMAGE_THUMB . '\/\d+(x\d+){0,1})\/' . self::IMAGE_THUMB . '\/\d+\/(.*)/i';

        return preg_replace($pattern, '$1/$3', $path);
    }
}
