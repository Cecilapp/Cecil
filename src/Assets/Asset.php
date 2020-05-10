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
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var string[] */
    protected $pathinfo = [];
    /** @var array */
    protected $data = [];
    /** @var bool */
    protected $compiled = false;
    /** @var bool */
    protected $versioned = false;
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

        $this->pathinfo = pathinfo($path);

        // handles options
        $minify = (bool) $this->config->get('assets.minify.auto');
        $version = (bool) $this->config->get('assets.version.auto');
        $attributes = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // set data
        $this->data['file'] = $filePath;
        $this->data['path'] = $path;
        $this->data['ext'] = $this->pathinfo['extension'];
        $this->data['type'] = explode('/', mime_content_type($filePath))[0];
        $this->data['content'] = file_get_contents($filePath);
        $this->data['attributes'] = $attributes;

        // compiling
        if ((bool) $this->config->get('assets.sass.auto')) {
            $this->compile();
        }
        // versionning
        if ($version) {
            $this->version();
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
     * Compiles a SCSS.
     *
     * @return self
     */
    public function compile(): self
    {
        if ($this->compiled) {
            return $this;
        }

        $data = $this->data;

        if ($data['ext'] != 'scss') {
            return $this;
        }

        $this->data['path'] = preg_replace('/scss/m', 'css', $this->data['path']);
        $this->data['ext'] = 'css';

        if ($this->exists($this->data['path'])) {
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
                $scssPhp->addImportPath(Util::joinPath(dirname($data['file']), $dir));
                foreach ($themes as $theme) {
                    $scssPhp->addImportPath(Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir")));
                }
            }
            $scssPhp->setVariables($this->config->get('assets.sass.variables') ?? []);
            $scssPhp->setFormatter('ScssPhp\ScssPhp\Formatter\\'.ucfirst($this->config->get('assets.sass.style')));
            $this->data['content'] = $scssPhp->compile($data['content']);
            $cache->set($cacheKey, $this->data['content']);
        }
        $this->data['content'] = $cache->get($cacheKey, $this->data['content']);

        $this->save($data['path']);

        $this->compiled = true;

        return $this;
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

        $data = $this->data;

        $version = $this->builder->time;
        if ($this->config->get('assets.version.strategy') == 'static') {
            $version = $this->config->get('assets.version.value');
        }

        $this->data['path'] = preg_replace('/'.$data['ext'].'$/m', "$version.".$data['ext'], $this->data['path']);

        if ($this->exists($this->data['path'])) {
            return $this;
        }

        $this->save($data['path']);

        $this->versioned = true;

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

        $data = $this->data;

        if ($data['ext'] == 'scss') {
            $this->compile();
        }

        if ($data['ext'] != 'css' && $data['ext'] != 'js') {
            return $this;
        }

        if (substr($this->data['path'], -7) != 'min.css' && substr($this->data['path'], -6) != 'min.js') {
            $this->data['path'] = preg_replace('/'.$data['ext'].'$/m', 'min.'.$data['ext'], $this->data['path']);
            $this->data['ext'] = \sprintf('min.%s', $data['ext']);
        }

        if ($this->exists($this->data['path'])) {
            return $this;
        }

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this);
        if (!$cache->has($cacheKey)) {
            switch ($data['ext']) {
                case 'css':
                    $minifier = new Minify\CSS($data['content']);
                    break;
                case 'js':
                    $minifier = new Minify\JS($data['content']);
                    break;
                default:
                    throw new Exception(sprintf('Not able to minify "%s"', $data['path']));
            }
            $this->data['content'] = $minifier->minify();
            $cache->set($cacheKey, $this->data['content']);
        }
        $this->data['content'] = $cache->get($cacheKey, $this->data['content']);

        $this->save($data['path']);

        $this->minified = true;

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

    /**
     * Saves file (and deletes previous file).
     *
     * @param string $previousPath
     *
     * @return void
     */
    private function save(string $previousPath = null): void
    {
        if (!$this->builder->getBuildOptions()['dry-run']) {
            dump('save', $this->data['path']);
            Util::getFS()->dumpFile(Util::joinFile($this->config->getOutputPath(), $this->data['path']), $this->data['content']);
            /*if (!empty($previousPath)) {
                Util::getFS()->remove(Util::joinFile($this->config->getOutputPath(), $previousPath));
            }*/
        }
    }

    /**
     * Checks if file is already saved/writen.
     *
     * @param string $path
     *
     * @return bool
     */
    private function exists(string $path): bool
    {
        return is_file(Util::joinFile($this->config->getOutputPath(), $path));
    }
}
