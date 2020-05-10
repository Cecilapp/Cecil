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

class Asset implements \ArrayAccess
{
    const ASSETS_OUTPUT_DIR = '/';

    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var bool */
    /** @var array */
    protected $data = [];
    protected $versioned = false;
    /** @var bool */
    protected $compiled = false;
    /** @var bool */
    protected $minified = false;

    /**
     * Creates an Asset from file.
     *
     * $options[
     *     'minify'     => true,
     *     'version'    => true,
     *     'attributes' => ['title' => 'Titre'],
     * ];
     *
     * @param Builder    $builder
     * @param string     $path
     * @param array|null $options
     */
    public function __construct(Builder $builder, string $path, array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        $path = '/'.ltrim($path, '/');

        if (false === $filePath = $this->findFile($path)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }

        $pathinfo = pathinfo($path);

        // handles options
        $minify = (bool) $this->config->get('assets.minify.auto');
        $version = (bool) $this->config->get('assets.version.auto');
        $attributes = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // set data
        $this->data['file'] = $filePath;
        $this->data['path'] = Util::joinPath(self::ASSETS_OUTPUT_DIR, $path);
        $this->data['ext'] = $pathinfo['extension'];
        $this->data['type'] = explode('/', mime_content_type($filePath))[0];
        $this->data['source'] = file_get_contents($filePath);
        $this->data['content'] = $this->data['source'];
        $this->data['attributes'] = $attributes;

        // versionning
        if ($version) {
            $this->version();
        }
        // compiling
        if ((bool) $this->config->get('assets.sass.auto')) {
            $this->compile();
        }
        // minifying
        if ($minify) {
            $this->minify();
        }
    }

    /**
     * Returns Asset path.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->data['path'];
    }

    /**
     * Versions a file.
     *
     * @return self
     */
    public function version(): self
    {
        if ($this->versioned) {
            return $this;
        }

        switch ($this->config->get('assets.version.strategy')) {
            case 'static':
                $version = $this->config->get('assets.version.value');
                break;
            case 'buildtime':
                $version = $this->builder->time;
                break;
            case 'today':
            default:
                $version = date('Ymd');
                break;
        }

        if ($this->config->get('assets.version.strategy') == 'static') {
            $version = $this->config->get('assets.version.value');
        }
        $this->data['path'] = preg_replace(
            '/'.$this->data['ext'].'$/m',
            "$version.".$this->data['ext'],
            $this->data['path']
        );

        $this->versioned = true;

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
            // import
            $scssDir = $this->config->get('assets.sass.dir') ?? [];
            $themes = $this->config->getTheme() ?? [];
            foreach ($scssDir as $dir) {
                $scssPhp->addImportPath(Util::joinPath($this->config->getStaticPath(), $dir));
                $scssPhp->addImportPath(Util::joinPath(dirname($this->data['file']), $dir));
                foreach ($themes as $theme) {
                    $scssPhp->addImportPath(Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir")));
                }
            }
            $scssPhp->setVariables($this->config->get('assets.sass.variables') ?? []);
            $scssPhp->setFormatter('ScssPhp\ScssPhp\Formatter\\'.ucfirst($this->config->get('assets.sass.style')));
            $this->data['path'] = preg_replace('/scss/m', 'css', $this->data['path']);
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
     * Hashing an asset with sha384.
     * Useful for SRI (Subresource Integrity).
     *
     * @see https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity
     *
     * @return string
     */
    public function getHash(): string
    {
        return sprintf('sha384-%s', base64_encode(hash('sha384', $this->data['content'], true)));
    }

    /**
     * Saves file.
     *
     * @throws Exception
     *
     * @return void
     */
    public function save(): void
    {
        $file = Util::joinFile($this->config->getOutputPath(), $this->data['path']);
        if (!$this->builder->getBuildOptions()['dry-run']/* && !is_file($file)*/) {
            try {
                Util::getFS()->dumpFile($file, $this->data['content']);
            } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
                throw new Exception(\sprintf('Can\'t save asset "%s"', $this->data['path']));
            }
        }
    }

    /**
     * Try to find a static file (in site or theme(s)) if exists or returns false.
     *
     * @param string $path
     *
     * @return string|false
     */
    private function findFile(string $path)
    {
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);
        if (Util::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks in each theme
        foreach ($this->config->getTheme() as $theme) {
            $filePath = Util::joinFile($this->config->getThemeDirPath($theme, 'static'), $path);
            if (Util::getFS()->exists($filePath)) {
                return $filePath;
            }
        }

        return false;
    }
}
