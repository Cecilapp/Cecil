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
    /** @var array */
    protected $data = [];
    /** @var bool */
    protected $compiled = false;
    /** @var bool */
    protected $minified = false;

    /**
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
        $save = false;

        // handles options
        $minify = (bool) $this->config->get('assets.minify');
        $version = (bool) $this->config->get('assets.version');
        $attributes = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // set data
        $this->data['file'] = $filePath;
        $this->data['path'] = $path;
        $this->data['ext'] = $pathinfo['extension'];
        $this->data['type'] = explode('/', mime_content_type($filePath))[0];
        $this->data['content'] = '';
        $this->data['content'] = file_get_contents($filePath);
        $this->data['attributes'] = $attributes;

        // versionning
        if ($version) {
            $this->data['path'] = \sprintf(
                '%s.%s.%s',
                Util::joinPath($pathinfo['dirname'], $pathinfo['filename']),
                $this->builder->time,
                $pathinfo['extension']
            );
            $save = true;
        }

        // minify
        if ($minify) {
            $this->minify();
        }

        if ($save) {
            $this->save($oldPath);
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
     * @throws Exception
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

        $oldPath = $this->data['path'];
        $this->data['path'] = preg_replace('/scss/m', 'css', $this->data['path']);
        $this->data['ext'] = 'css';

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($this);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            $variables = $this->config->get('assets.sass.variables') ?? [];
            $scssDir = $this->config->get('assets.sass.dir') ?? [];
            $themes = $this->config->getTheme() ?? [];
            foreach ($scssDir as $dir) {
                $scssPhp->addImportPath(Util::joinPath($this->config->getStaticPath(), $dir));
                $scssPhp->addImportPath(Util::joinPath(dirname($this->data['file']), $dir));
                foreach ($themes as $theme) {
                    $scssPhp->addImportPath(Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir")));
                }
            }
            $scssPhp->setVariables($variables);
            $scssPhp->setFormatter('ScssPhp\ScssPhp\Formatter\\'.ucfirst($this->config->get('assets.sass.style')));
            $this->data['content'] = $scssPhp->compile($this->data['content']);
            $cache->set($cacheKey, $this->data['content']);
        }

        $this->data['content'] = $cache->get($cacheKey, $this->data['content']);

        $this->save($oldPath);

        $this->compiled = true;

        return $this;
    }

    /**
     * Minifying a CSS or a JS.
     *
     * @throws Exception
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

        $oldPath = $this->data['path'];
        $this->data['path'] = \sprintf('%s.min.%s', substr($this->data['path'], 0, -strlen('.'.$this->data['ext'])), $this->data['ext']);

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
            $cache->set($cacheKey, $this->data['content']);
        }
        $this->data['content'] = $cache->get($cacheKey, $this->data['content']);

        $this->save($oldPath);

        $this->minified = true;

        return $this;
    }

    /**
     * Save file.
     *
     * @param string $oldPath
     *
     * @return void
     */
    public function save(string $oldPath = null): void
    {
        if (!$this->builder->getBuildOptions()['dry-run']) {
            Util::getFS()->dumpFile(Util::joinFile($this->config->getOutputPath(), $this->data['path']), $this->data['content']);
            if (!empty($oldPath)) {
                Util::getFS()->remove(Util::joinFile($this->config->getOutputPath(), $oldPath));
            }
        }
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
}
