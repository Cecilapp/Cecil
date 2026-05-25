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

use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\ConfigException;
use Cecil\Util;
use ScssPhp\ScssPhp\Compiler as ScssCompiler;
use ScssPhp\ScssPhp\OutputStyle;

/**
 * Compiles SCSS assets to CSS.
 */
class Compiler
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * Compiles SCSS to CSS.
     * Returns the updated data array, unchanged if not a SCSS file.
     *
     * @param array $data Asset data array (must contain 'ext', 'path', 'file', 'content')
     *
     * @return array Updated data array with CSS content
     *
     * @throws ConfigException
     */
    public function compile(array $data): array
    {
        if ($data['ext'] !== 'scss') {
            return $data;
        }

        $scssPhp = new ScssCompiler();

        // import paths
        $importDir = [];
        $importDir[] = Util::joinPath($this->config->getStaticPath());
        $importDir[] = Util::joinPath($this->config->getAssetsPath());
        $scssDir = (array) $this->config->get('assets.compile.import');
        $themes = $this->config->getTheme() ?? [];
        foreach ($scssDir as $dir) {
            $importDir[] = Util::joinPath($this->config->getStaticPath(), $dir);
            $importDir[] = Util::joinPath($this->config->getAssetsPath(), $dir);
            $importDir[] = Util::joinPath(\dirname($data['file']), $dir);
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
            $assetDirPos = strrpos($data['file'], DIRECTORY_SEPARATOR . $assetDir . DIRECTORY_SEPARATOR);
            $fileRelPath = substr($data['file'], $assetDirPos + 8);
            $filePath = Util::joinFile($this->config->getOutputPath(), $fileRelPath);
            $importDir[] = \dirname($filePath);
            foreach ($scssDir as $dir) {
                $importDir[] = Util::joinFile($this->config->getOutputPath(), $dir);
            }
            $scssPhp->setImportPaths(array_unique($importDir));
            $scssPhp->setSourceMap(ScssCompiler::SOURCE_MAP_INLINE);
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
        $scssPhp->setOutputStyle($outputStyle === 'compressed' ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);

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
        $data['path'] = preg_replace('/sass|scss/m', 'css', $data['path']);
        $data['ext'] = 'css';
        $data['type'] = 'text';
        $data['subtype'] = 'text/css';
        $data['content'] = $scssPhp->compileString($data['content'])->getCss();
        $data['size'] = \strlen($data['content']);

        $this->builder->getLogger()->debug(\sprintf('Asset compiled: "%s"', $data['path']));

        return $data;
    }
}
