<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;
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
    /** @var bool */
    protected $ignore_missing = false;

    /**
     * Creates an Asset from file(s) path.
     *
     * $options[
     *     'fingerprint'    => true,
     *     'minify'         => true,
     *     'filename'       => '',
     *     'ignore_missing' => false,
     * ];
     *
     * @param Builder      $builder
     * @param string|array $path
     * @param array|null   $options
     */
    public function __construct(Builder $builder, $path, array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $path = is_array($path) ? $path : [$path];

        // handles options
        $fingerprint = (bool) $this->config->get('assets.fingerprint.enabled');
        $minify = (bool) $this->config->get('assets.minify.enabled');
        $filename = '';
        $ignore_missing = false;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);
        $this->ignore_missing = $ignore_missing;

        // loads file(s)
        $file = [];
        $prevType = '';
        $prevExt = '';
        foreach ($path as $p) {
            $file = $this->loadFile($p, $ignore_missing);
            if ($file['missing']) {
                $this->data['path'] = '';

                continue;
            }

            // bundle: same type only
            if (!empty($prevType) && $file['type'] != $prevType) {
                throw new Exception(\sprintf('Asset bundle type error (%s != %s).', $file['type'], $prevType));
            }
            // bundle: same extension only
            if (!empty($prevExt) && $file['ext'] != $prevExt) {
                throw new Exception(\sprintf('Asset bundle extension error (%s != %s).', $file['ext'], $prevExt));
            }

            // set data
            $this->data['file'] = $file['filepath'];
            $this->data['path'] = $file['path'];
            $this->data['ext'] = $file['ext'];
            $this->data['type'] = $file['type'];
            $this->data['subtype'] = $file['subtype'];
            $this->data['size'] = $file['size'];
            $this->data['source'] = $file['content'];
            $this->data['content'] .= $file['content'];

            // filename
            if (!empty($filename)) {
                $this->data['path'] = $filename;
            }

            $prevType = $file['type'];
            $prevExt = $file['ext'];
        }
        // bundle: define path
        if (count($path) > 1) {
            $this->data['path'] = $filename;
            if (empty($filename)) {
                switch ($this->data['ext']) {
                    case 'scss':
                    case 'css':
                        $this->data['path'] = 'styles.'.$file['ext'];
                        break;
                    case 'js':
                        $this->data['path'] = 'scripts.'.$file['ext'];
                        break;
                    default:
                        throw new Exception(\sprintf('Asset bundle supports "%s" files only.', 'scss, css and js'));
                }
            }
        }

        // fingerprinting
        if ($fingerprint) {
            $this->fingerprint();
        }
        // compiling
        if ((bool) $this->config->get('assets.compile.enabled')) {
            $this->compile();
        }
        // minifying
        if ($minify) {
            $this->minify();
        }
    }

    /**
     * Returns path.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->data['path'];
    }

    /**
     * Fingerprints a file.
     *
     * @return self
     */
    public function fingerprint(): self
    {
        if ($this->fingerprinted) {
            return $this;
        }

        $fingerprint = hash('md5', $this->data['source']);
        $this->data['path'] = preg_replace(
            '/\.'.$this->data['ext'].'$/m',
            ".$fingerprint.".$this->data['ext'],
            $this->data['path']
        );

        $this->fingerprinted = true;

        return $this;
    }

    /**
     * Compiles a SCSS.
     *
     * @return self
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
        $cacheKey = $cache->createKeyFromAsset($this);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            // import path
            $scssPhp->addImportPath(Util::joinPath($this->config->getStaticPath()));
            $scssDir = $this->config->get('assets.compile.import') ?? [];
            $themes = $this->config->getTheme() ?? [];
            foreach ($scssDir as $dir) {
                $scssPhp->addImportPath(Util::joinPath($this->config->getStaticPath(), $dir));
                $scssPhp->addImportPath(Util::joinPath(dirname($this->data['file']), $dir));
                foreach ($themes as $theme) {
                    $scssPhp->addImportPath(Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir")));
                }
            }
            // output style
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!in_array($outputStyle, $outputStyles)) {
                throw new Exception(\sprintf('Scss output style "%s" doesn\'t exists.', $outputStyle));
            }
            $scssPhp->setOutputStyle($outputStyle);
            // variables
            $scssPhp->setVariables($this->config->get('assets.compile.variables') ?? []);
            // update data
            $this->data['path'] = preg_replace('/sass|scss/m', 'css', $this->data['path']);
            $this->data['ext'] = 'css';
            $this->data['content'] = $scssPhp->compile($this->data['content']);
            $this->compiled = true;
            $cache->set($cacheKey, $this->data);
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Minifying a CSS or a JS.
     *
     * @return self
     */
    public function minify(): self
    {
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
            $this->minified;

            return $this;
        }

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this);
        if (!$cache->has($cacheKey)) {
            switch ($this->data['ext']) {
                case 'css':
                    $minifier = new Minify\CSS($this->data['content']);
                    break;
                case 'js':
                    $minifier = new Minify\JS($this->data['content']);
                    break;
                default:
                    throw new Exception(sprintf('Not able to minify "%s"', $this->data['path']));
            }
            $this->data['path'] = preg_replace(
                '/\.'.$this->data['ext'].'$/m',
                '.min.'.$this->data['ext'],
                $this->data['path']
            );
            $this->data['content'] = $minifier->minify();
            $this->minified = true;
            $cache->set($cacheKey, $this->data);
        }
        $this->data = $cache->get($cacheKey);

        return $this;
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetSet($offset, $value)
    {
        if (!is_null($offset)) {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Hashing content of an asset with the specified algo, sha384 by default.
     * Used for SRI (Subresource Integrity).
     *
     * @see https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity
     *
     * @return string
     */
    public function getIntegrity(string $algo = 'sha384'): string
    {
        return \sprintf('%s-%s', $algo, base64_encode(hash($algo, $this->data['content'], true)));
    }

    /**
     * Returns the width of an image.
     *
     * @return false|int
     */
    public function getWidth()
    {
        if (false === $size = $this->getImageSize()) {
            return false;
        }

        return $size[0];
    }

    /**
     * Returns the height of an image.
     *
     * @return false|int
     */
    public function getHeight()
    {
        if (false === $size = $this->getImageSize()) {
            return false;
        }

        return $size[1];
    }

    /**
     * Returns MP3 file infos.
     *
     * @see https://github.com/wapmorgan/Mp3Info
     *
     * @return Mp3Info
     */
    public function getAudio(): Mp3Info
    {
        return new Mp3Info($this->data['file']);
    }

    /**
     * Saves file.
     * Note: a file from `static/` with the same name will be overridden.
     *
     * @throws Exception
     *
     * @return void
     */
    public function save(): void
    {
        $file = Util::joinFile($this->config->getOutputPath(), $this->data['path']);
        if (!$this->builder->getBuildOptions()['dry-run']) {
            try {
                Util\File::getFS()->dumpFile($file, $this->data['content']);
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                if (!$this->ignore_missing) {
                    throw new Exception(\sprintf('Can\'t save asset "%s"', $this->data['path']));
                }
            }
        }
    }

    /**
     * Load file data.
     *
     * @param string $path           Relative path or URL.
     * @param bool   $ignore_missing Don't throw exception if file is missing.
     *
     * @return array
     */
    private function loadFile(string $path, bool $ignore_missing = false): array
    {
        $file = [];

        if (false === $filePath = $this->findFile($path)) {
            if ($ignore_missing) {
                $file['missing'] = true;

                return $file;
            }

            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }

        if (Util\Url::isUrl($path)) {
            $path = Util::joinPath('assets', parse_url($path, PHP_URL_HOST), parse_url($path, PHP_URL_PATH));
        }
        $path = '/'.ltrim($path, '/');

        $pathinfo = pathinfo($path);
        list($type, $subtype) = Util\File::getMimeType($filePath);
        $content = Util\File::fileGetContents($filePath);

        $file['filepath'] = $filePath;
        $file['path'] = $path;
        $file['ext'] = $pathinfo['extension'];
        $file['type'] = $type;
        $file['subtype'] = $subtype;
        $file['size'] = filesize($filePath);
        $file['content'] = $content;

        return $file;
    }

    /**
     * Try to find the file:
     *   1. remote (if $path is a valid URL)
     *   2. in static/
     *   3. in themes/<theme>/static/
     * Returns local file path or false if file don't exists.
     *
     * @param string $path
     *
     * @return string|false
     */
    private function findFile(string $path)
    {
        // in case of remote file: save it and returns cached file path
        if (Util\Url::isUrl($path)) {
            $url = $path;
            $cache = new Cache($this->builder, 'assets');
            $relativePath = parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH);
            $filePath = Util::joinFile($this->config->getCacheAssetsPath(), $relativePath);
            $cacheKey = $cache->createKeyFromFile($url, $relativePath);
            if (!$cache->has($cacheKey) || !file_exists($filePath)) {
                if (!Util\Url::isRemoteFileExists($url)) {
                    return false;
                }
                if (false === $content = Util\File::fileGetContents($url)) {
                    return false;
                }
                if (strlen($content) <= 1) {
                    throw new Exception(sprintf('Asset at "%s" is empty.', $url));
                }
                $cache->set($cacheKey, $content);
                Util\File::getFS()->dumpFile($filePath, $cache->get($cacheKey));
            }

            return $filePath;
        }

        // checks in static/
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);
        if (Util\File::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks in each themes/<theme>/static/
        foreach ($this->config->getTheme() as $theme) {
            $filePath = Util::joinFile($this->config->getThemeDirPath($theme, 'static'), $path);
            if (Util\File::getFS()->exists($filePath)) {
                return $filePath;
            }
        }

        return false;
    }

    /**
     * Returns image size informations.
     *
     * See https://www.php.net/manual/function.getimagesize.php
     *
     * @return false|array
     */
    private function getImageSize()
    {
        if (!$this->data['type'] == 'image') {
            return false;
        }

        if (false === $size = getimagesizefromstring($this->data['content'])) {
            return false;
        }

        return $size;
    }
}
