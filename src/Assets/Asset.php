<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

class Asset implements \ArrayAccess
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    /** @var array */
    protected $data = [];

    /** @var bool */
    protected $fingerprinted = false;

    /** @var bool */
    protected $compiled = false;

    /** @var bool */
    protected $minified = false;

    /**
     * Creates an Asset from a file path, an array of files path or an URL.
     *
     * @param Builder      $builder
     * @param string|array $paths
     * @param array|null   $options
     *
     * options:
     * [
     *     'fingerprint' => <bool>,
     *     'minify' => <bool>,
     *     'optimize' => <bool>,
     *     'filename' => <string>,
     *     'ignore_missing' => <bool>,
     *     'remote_fallback' => <string>,
     *     'force_slash' => <bool>
     * ]
     *
     * @throws RuntimeException
     */
    public function __construct(Builder $builder, string|array $paths, array|null $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $paths = \is_array($paths) ? $paths : [$paths];
        array_walk($paths, function ($path) {
            if (!\is_string($path)) {
                throw new RuntimeException(\sprintf('The path of an asset must be a string ("%s" given).', \gettype($path)));
            }
            if (empty($path)) {
                throw new RuntimeException('The path of an asset can\'t be empty.');
            }
            if (substr($path, 0, 2) == '..') {
                throw new RuntimeException(\sprintf('The path of asset "%s" is wrong: it must be directly relative to "assets" or "static" directory, or a remote URL.', $path));
            }
        });
        $this->data = [
            'file'           => '',    // absolute file path
            'files'          => [],    // bundle: array of files path
            'filename'       => '',    // bundle: filename
            'path'           => '',    // public path to the file
            'url'            => null,  // URL if it's a remote file
            'missing'        => false, // if file not found but missing allowed: 'missing' is true
            'ext'            => '',    // file extension
            'type'           => '',    // file type (e.g.: image, audio, video, etc.)
            'subtype'        => '',    // file media type (e.g.: image/png, audio/mp3, etc.)
            'size'           => 0,     // file size (in bytes)
            'width'          => 0,     // image width (in pixels)
            'height'         => 0,     // image height (in pixels)
            'exif'           => [],    // exif data
            'content'        => '',    // file content
        ];

        // handles options
        $fingerprint = $this->config->isEnabled('assets.fingerprint');
        $minify = $this->config->isEnabled('assets.minify');
        $optimize = $this->config->isEnabled('assets.images.optimize');
        $filename = '';
        $ignore_missing = false;
        $remote_fallback = null;
        $force_slash = true;
        extract(\is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // fill data array with file(s) informations
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = \sprintf('%s__%s', $filename ?: implode('_', $paths), $this->builder->getVersion());
        if (!$cache->has($cacheKey)) {
            $pathsCount = \count($paths);
            $file = [];
            for ($i = 0; $i < $pathsCount; $i++) {
                // loads file(s)
                $file[$i] = $this->loadFile($paths[$i], $ignore_missing, $remote_fallback, $force_slash);
                // bundle: same type only
                if ($i > 0) {
                    if ($file[$i]['type'] != $file[$i - 1]['type']) {
                        throw new RuntimeException(\sprintf('Asset bundle type error (%s != %s).', $file[$i]['type'], $file[$i - 1]['type']));
                    }
                }
                // missing allowed = empty path
                if ($file[$i]['missing']) {
                    $this->data['missing'] = true;
                    $this->data['path'] = $file[$i]['path'];

                    continue;
                }
                // set data
                $this->data['content'] .= $file[$i]['content'];
                $this->data['size'] += $file[$i]['size'];
                if ($i == 0) {
                    $this->data['file'] = $file[$i]['filepath'];
                    $this->data['filename'] = $file[$i]['path'];
                    $this->data['path'] = $file[$i]['path'];
                    $this->data['url'] = $file[$i]['url'];
                    $this->data['ext'] = $file[$i]['ext'];
                    $this->data['type'] = $file[$i]['type'];
                    $this->data['subtype'] = $file[$i]['subtype'];
                    // image: width, height and exif
                    if ($this->data['type'] == 'image') {
                        $this->data['width'] = $this->getWidth();
                        $this->data['height'] = $this->getHeight();
                        if ($this->data['subtype'] == 'image/jpeg') {
                            $this->data['exif'] = Util\File::readExif($file[$i]['filepath']);
                        }
                    }
                    // bundle default filename
                    if ($pathsCount > 1 && empty($filename)) {
                        switch ($this->data['ext']) {
                            case 'scss':
                            case 'css':
                                $filename = '/styles.css';
                                break;
                            case 'js':
                                $filename = '/scripts.js';
                                break;
                            default:
                                throw new RuntimeException(\sprintf('Asset bundle supports %s files only.', '.scss, .css and .js'));
                        }
                    }
                    // bundle filename and path
                    if (!empty($filename)) {
                        $this->data['filename'] = $filename;
                        $this->data['path'] = '/' . ltrim($filename, '/');
                    }
                }
                // bundle files path
                $this->data['files'][] = $file[$i]['filepath'];
            }
            // fingerprinting
            if ($fingerprint) {
                $this->fingerprint();
            }
            $cache->set($cacheKey, $this->data);
            $this->builder->getLogger()->debug(\sprintf('Asset created: "%s"', $this->data['path']));
            // optimizing images files
            if ($optimize && $this->data['type'] == 'image' && !$this->isImageInCdn()) {
                $this->optimize($cache->getContentFilePathname($this->data['path']), $this->data['path']);
            }
        }
        $this->data = $cache->get($cacheKey);
        // compiling (Sass files)
        if ($this->config->isEnabled('assets.compile')) {
            $this->compile();
        }
        // minifying (CSS and JavScript files)
        if ($minify) {
            $this->minify();
        }
    }

    /**
     * Returns path.
     *
     * @throws RuntimeException
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
     * Fingerprints a file.
     */
    public function fingerprint(): self
    {
        if ($this->fingerprinted) {
            return $this;
        }

        $fingerprint = hash('md5', $this->data['content']);
        $this->data['path'] = preg_replace(
            '/\.' . $this->data['ext'] . '$/m',
            ".$fingerprint." . $this->data['ext'],
            $this->data['path']
        );

        $this->fingerprinted = true;

        return $this;
    }

    /**
     * Compiles a SCSS.
     *
     * @throws RuntimeException
     */
    public function compile(): self
    {
        if ($this->compiled) {
            return $this;
        }

        if ($this->data['ext'] != 'scss') {
            return $this;
        }

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this, ['compiled']);
        if (!$cache->has($cacheKey)) {
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
            // source map
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
            // output style
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!\in_array($outputStyle, $outputStyles)) {
                throw new ConfigException(\sprintf('"%s" value must be "%s".', 'assets.compile.style', implode('" or "', $outputStyles)));
            }
            $scssPhp->setOutputStyle($outputStyle == 'compressed' ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
            // variables
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
            $this->compiled = true;
            $cache->set($cacheKey, $this->data);
            $this->builder->getLogger()->debug(\sprintf('Asset compiled: "%s"', $this->data['path']));
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Minifying a CSS or a JS.
     *
     * @throws RuntimeException
     */
    public function minify(): self
    {
        // disable minify to preserve inline source map
        if ($this->builder->isDebug() && $this->config->isEnabled('assets.compile.sourcemap')) {
            return $this;
        }

        if ($this->minified) {
            return $this;
        }

        if ($this->data['ext'] == 'scss') {
            $this->compile();
        }

        if ($this->data['ext'] != 'css' && $this->data['ext'] != 'js') {
            return $this;
        }

        if (substr($this->data['path'], -8) == '.min.css' || substr($this->data['path'], -7) == '.min.js') {
            $this->minified = true;

            return $this;
        }

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this, ['minified']);
        if (!$cache->has($cacheKey)) {
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
            $this->data['path'] = preg_replace(
                '/\.' . $this->data['ext'] . '$/m',
                '.min.' . $this->data['ext'],
                $this->data['path']
            );
            $this->data['content'] = $minifier->minify();
            $this->data['size'] = \strlen($this->data['content']);
            $this->minified = true;
            $cache->set($cacheKey, $this->data);
            $this->builder->getLogger()->debug(\sprintf('Asset minified: "%s"', $this->data['path']));
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Optimizing $filepath image.
     * Returns the new file size.
     */
    public function optimize(string $filepath, string $path): int
    {
        $quality = (int) $this->config->get('assets.images.quality');
        $message = \sprintf('Asset processed: "%s"', $path);
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
     * Resizes an image with a new $width.
     *
     * @throws RuntimeException
     */
    public function resize(int $width): self
    {
        if ($this->data['missing']) {
            throw new RuntimeException(\sprintf('Not able to resize "%s": file not found.', $this->data['path']));
        }
        if ($this->data['type'] != 'image') {
            throw new RuntimeException(\sprintf('Not able to resize "%s": not an image.', $this->data['path']));
        }
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
        $cacheKey = $cache->createKeyFromAsset($assetResized, ["{$width}x", "q$quality"]);
        if (!$cache->has($cacheKey)) {
            $assetResized->data['content'] = Image::resize($assetResized, $width, $quality);
            $assetResized->data['path'] = '/' . Util::joinPath(
                (string) $this->config->get('assets.target'),
                'thumbnails',
                (string) $width,
                $assetResized->data['path']
            );
            $assetResized->data['height'] = $assetResized->getHeight();
            $assetResized->data['size'] = \strlen($assetResized->data['content']);

            $cache->set($cacheKey, $assetResized->data);
            $this->builder->getLogger()->debug(\sprintf('Asset resized: "%s" (%sx)', $assetResized->data['path'], $width));
        }
        $assetResized->data = $cache->get($cacheKey);

        return $assetResized;
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
        $tags = ["q$quality"];
        if ($this->data['width']) {
            array_unshift($tags, "{$this->data['width']}x");
        }
        $cacheKey = $cache->createKeyFromAsset($asset, $tags);
        if (!$cache->has($cacheKey)) {
            $asset->data['content'] = Image::convert($asset, $format, $quality);
            $asset->data['path'] = preg_replace('/\.' . $this->data['ext'] . '$/m', ".$format", $this->data['path']);
            $asset->data['size'] = \strlen($asset->data['content']);
            $cache->set($cacheKey, $asset->data);
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
        if (!Util\File::getFS()->exists($cache->getContentFilePathname($this->data['path']))) {
            throw new RuntimeException(\sprintf('Can\'t add "%s" to assets list: file not found.', $this->data['path']));
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
     * Load a file and store extracted data in an array.
     *
     * @throws RuntimeException
     *
     * @return string[]
     */
    private function loadFile(string $path, bool $ignore_missing = false, ?string $remote_fallback = null, bool $force_slash = true): array
    {
        $file = [
            'url'      => null,
            'filepath' => null,
            'path'     => null,
            'ext'      => null,
            'type'     => null,
            'subtype'  => null,
            'size'     => null,
            'content'  => null,
            'missing'  => false,
        ];

        // try to find file locally and returns the file path
        try {
            $filePath = $this->locateFile($path, $remote_fallback);
        } catch (RuntimeException $e) {
            if ($ignore_missing) {
                $file['path'] = $path;
                $file['missing'] = true;

                return $file;
            }

            throw new RuntimeException(\sprintf('Can\'t load asset file "%s" (%s).', $path, $e->getMessage()));
        }

        // in case of an URL, update $path
        if (Util\Url::isUrl($path)) {
            $file['url'] = $path;
            $path = Util::joinPath(
                (string) $this->config->get('assets.target'),
                Util\File::getFS()->makePathRelative($filePath, $this->config->getAssetsRemotePath())
            );
            // trick: the `remote_fallback` file is in assets/ dir (not in cache/assets/remote/
            if (substr(Util\File::getFS()->makePathRelative($filePath, $this->config->getAssetsRemotePath()), 0, 2) == '..') {
                $path = Util::joinPath(
                    (string) $this->config->get('assets.target'),
                    Util\File::getFS()->makePathRelative($filePath, $this->config->getAssetsPath())
                );
            }
            $force_slash = true;
        }

        // force leading slash?
        if ($force_slash) {
            $path = '/' . ltrim($path, '/');
        }

        // get content and content type
        $content = Util\File::fileGetContents($filePath);
        list($type, $subtype) = Util\File::getMediaType($filePath);

        $file['filepath'] = $filePath;
        $file['path'] = $path;
        $file['ext'] = pathinfo($path)['extension'] ?? '';
        $file['type'] = $type;
        $file['subtype'] = $subtype;
        $file['size'] = filesize($filePath);
        $file['content'] = $content;
        $file['missing'] = false;

        return $file;
    }

    /**
     * Try to locate the file:
     *   1. remotely (if $path is a valid URL)
     *   2. in static|assets/
     *   3. in themes/<theme>/static|assets/
     * Returns local file path or throw an exception.
     *
     * @return string local file path
     *
     * @throws RuntimeException
     */
    private function locateFile(string $path, ?string $remote_fallback = null): string
    {
        // in case of a remote file: save it locally and returns its path
        if (Util\Url::isUrl($path)) {
            $url = $path;
            // create relative path
            $urlHost = parse_url($path, PHP_URL_HOST);
            $urlPath = parse_url($path, PHP_URL_PATH);
            $urlQuery = parse_url($path, PHP_URL_QUERY);
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            // Google Fonts hack
            if (Util\Str::endsWith($urlPath, '/css') || Util\Str::endsWith($urlPath, '/css2')) {
                $extension = 'css';
            }
            $relativePath = Page::slugify(\sprintf(
                '%s%s%s%s',
                $urlHost,
                $this->sanitize($urlPath),
                $urlQuery ? "-$urlQuery" : '',
                $urlQuery && $extension ? ".$extension" : ''
            ));
            $filePath = Util::joinFile($this->config->getAssetsRemotePath(), $relativePath);
            // save file
            if (!file_exists($filePath)) {
                try {
                    // get content
                    if (!Util\Url::isRemoteFileExists($url)) {
                        throw new RuntimeException(\sprintf('File "%s" doesn\'t exists', $url));
                    }
                    if (false === $content = Util\File::fileGetContents($url, true)) {
                        throw new RuntimeException(\sprintf('Can\'t get content of file "%s".', $url));
                    }
                    if (\strlen($content) <= 1) {
                        throw new RuntimeException(\sprintf('File "%s" is empty.', $url));
                    }
                } catch (RuntimeException $e) {
                    // if there is a fallback in assets/ returns it
                    if ($remote_fallback) {
                        $filePath = Util::joinFile($this->config->getAssetsPath(), $remote_fallback);
                        if (Util\File::getFS()->exists($filePath)) {
                            return $filePath;
                        }

                        throw new RuntimeException(\sprintf('Fallback file "%s" doesn\'t exists.', $filePath));
                    }

                    throw new RuntimeException($e->getMessage());
                }
                Util\File::getFS()->dumpFile($filePath, $content);
            }

            return $filePath;
        }

        // checks in assets/
        $filePath = Util::joinFile($this->config->getAssetsPath(), $path);
        if (Util\File::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks in each themes/<theme>/assets/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            $filePath = Util::joinFile($this->config->getThemeDirPath($theme, 'assets'), $path);
            if (Util\File::getFS()->exists($filePath)) {
                return $filePath;
            }
        }

        // checks in static/
        $filePath = Util::joinFile($this->config->getStaticTargetPath(), $path);
        if (Util\File::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks in each themes/<theme>/static/
        foreach ($this->config->getTheme() ?? [] as $theme) {
            $filePath = Util::joinFile($this->config->getThemeDirPath($theme, 'static'), $path);
            if (Util\File::getFS()->exists($filePath)) {
                return $filePath;
            }
        }

        throw new RuntimeException(\sprintf('Can\'t find file "%s".', $path));
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
     * Replaces some characters by '_'.
     */
    private function sanitize(string $string): string
    {
        return str_replace(['<', '>', ':', '"', '\\', '|', '?', '*'], '_', $string);
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
}
