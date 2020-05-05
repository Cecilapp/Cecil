<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\Twig;

use Cecil\Assets\Asset;
use Cecil\Assets\Cache;
use Cecil\Assets\Image;
use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;

/**
 * Class Twig\Extension.
 */
class Extension extends SlugifyExtension
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var Slugify */
    private static $slugifier;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => Page::SLUGIFY_PATTERN]);
        }

        parent::__construct(self::$slugifier);

        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cecil';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('filter_by', [$this, 'filterBy']),
            // sort
            new \Twig\TwigFilter('sort_by_title', [$this, 'sortByTitle']),
            new \Twig\TwigFilter('sort_by_weight', [$this, 'sortByWeight']),
            new \Twig\TwigFilter('sort_by_date', [$this, 'sortByDate']),
            // assets
            new \Twig\TwigFilter('url', [$this, 'createUrl']),
            new \Twig\TwigFilter('minify', [$this, 'minify']),
            new \Twig\TwigFilter('to_css', [$this, 'toCss']),
            new \Twig\TwigFilter('html', [$this, 'createHtmlElement']),
            new \Twig\TwigFilter('inline', [$this, 'getContent']),
            new \Twig\TwigFilter('minify_css', [$this, 'minifyCss']),
            new \Twig\TwigFilter('minify_js', [$this, 'minifyJs']),
            new \Twig\TwigFilter('scss_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('sass_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('resize', [$this, 'resize']),
            // content
            new \Twig\TwigFilter('slugify', [$this, 'slugifyFilter']),
            new \Twig\TwigFilter('excerpt', [$this, 'excerpt']),
            new \Twig\TwigFilter('excerpt_html', [$this, 'excerptHtml']),
            // deprecated
            new \Twig\TwigFilter('filterBy', [$this, 'filterBy'], ['deprecated' => true, 'alternative' => 'filter_by']),
            new \Twig\TwigFilter('sortByTitle', [$this, 'sortByTitle'], ['deprecated' => true, 'alternative' => 'sort_by_title']),
            new \Twig\TwigFilter('sortByWeight', [$this, 'sortByWeight'], ['deprecated' => true, 'alternative' => 'sort_by_weight']),
            new \Twig\TwigFilter('sortByDate', [$this, 'sortByDate'], ['deprecated' => true, 'alternative' => 'sort_by_date']),
            new \Twig\TwigFilter('minifyCSS', [$this, 'minifyCss'], ['deprecated' => true, 'alternative' => 'minifyCss']),
            new \Twig\TwigFilter('minifyJS', [$this, 'minifyJs'], ['deprecated' => true, 'alternative' => 'minifyJs']),
            new \Twig\TwigFilter('SCSStoCSS', [$this, 'scssToCss'], ['deprecated' => true, 'alternative' => 'scss_to_css']),
            new \Twig\TwigFilter('excerptHtml', [$this, 'excerptHtml'], ['deprecated' => true, 'alternative' => 'excerpt_html']),
            new \Twig\TwigFilter('urlize', [$this, 'slugifyFilter'], ['deprecated' => true, 'alternative' => 'slugify']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // assets
            new \Twig\TwigFunction('url', [$this, 'createUrl']),
            new \Twig\TwigFunction('asset', [$this, 'asset']),
            new \Twig\TwigFunction('hash', [$this, 'hashFile']),
            // content
            new \Twig\TwigFunction('readtime', [$this, 'readtime']),
            // others
            new \Twig\TwigFunction('getenv', [$this, 'getEnv']),
            // deprecated
            new \Twig\TwigFunction('minify', [$this, 'minify'], ['deprecated' => true, 'alternative' => 'minify filter']),
            new \Twig\TwigFunction('toCSS', [$this, 'toCss'], ['deprecated' => true, 'alternative' => 'to_css filter']),
        ];
    }

    /**
     * Filters by Section.
     *
     * Alias of `filterBy('section', $value)`.
     *
     * @param PagesCollection $pages
     * @param string          $section
     *
     * @return CollectionInterface
     */
    public function filterBySection(PagesCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filters by variable's name/value.
     *
     * @param PagesCollection $pages
     * @param string          $variable
     * @param string          $value
     *
     * @return CollectionInterface
     */
    public function filterBy(PagesCollection $pages, string $variable, string $value): CollectionInterface
    {
        $filteredPages = $pages->filter(function (Page $page) use ($variable, $value) {
            $notVirtual = false;
            if (!$page->isVirtual()) {
                $notVirtual = true;
            }
            // is a dedicated getter exists?
            $method = 'get'.ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return $notVirtual && true;
            }
            if ($page->getVariable($variable) == $value) {
                return $notVirtual && true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sorts by title.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByTitle(\Traversable $collection): array
    {
        $collection = iterator_to_array($collection);
        array_multisort(array_keys($collection), SORT_NATURAL | SORT_FLAG_CASE, $collection);

        return $collection;
    }

    /**
     * Sorts by weight.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByWeight(\Traversable $collection): array
    {
        $callback = function ($a, $b) {
            if (!isset($a['weight'])) {
                return 1;
            }
            if (!isset($b['weight'])) {
                return -1;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort($collection, $callback);

        return $collection;
    }

    /**
     * Sorts by date.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByDate(\Traversable $collection): array
    {
        $callback = function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort($collection, $callback);

        return $collection;
    }

    /**
     * Creates an URL.
     *
     * $options[
     *     'canonical' => true,
     *     'addhash'   => false,
     *     'format'    => 'json',
     * ];
     *
     * @param Page|Asset|string|null $value
     * @param array|null             $options
     *
     * @return mixed
     */
    public function createUrl($value = null, $options = null)
    {
        $baseurl = (string) $this->config->get('baseurl');
        $hash = md5((string) $this->config->get('time'));
        $base = '';

        // handles options
        $canonical = null;
        $addhash = false;
        $format = null;
        extract(is_array($options) ? $options : []);

        // set baseurl
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // value is empty: url()
        if (empty($value) || $value == '/') {
            return $base.'/';
        }

        // value is a Page item
        if ($value instanceof Page) {
            if (!$format) {
                $format = $value->getVariable('output');
                if (is_array($value->getVariable('output'))) {
                    $format = $value->getVariable('output')[0];
                }
                if (!$format) {
                    $format = 'html';
                }
            }
            $url = $value->getUrl($format, $this->config);
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // value is an Asset object
        if ($value instanceof Asset) {
            $asset = $value;
            $url = $asset['path'];
            if ($addhash) {
                $url .= '?'.$hash;
            }
            $url = $base.'/'.ltrim($url, '/');
            $asset['path'] = $url;

            return $asset;
        }

        // value is an external URL
        if (Util::isExternalUrl($value)) {
            $url = $value;

            return $url;
        }

        // value is a string
        $value = Util::joinPath($value);

        // value is (certainly) a path to a ressource (ie: 'path/file.pdf')
        if (false !== strpos($value, '.')) {
            $url = $value;
            if ($addhash) {
                $url .= '?'.$hash;
            }
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // others cases
        $url = $base.'/'.$value;

        // value is a page ID (ie: 'path/my-page')
        try {
            $pageId = $this->slugifyFilter($value);
            $page = $this->builder->getPages()->get($pageId);
            $url = $this->createUrl($page, $options);
        } catch (\DomainException $e) {
            // nothing to do
        }

        return $url;
    }

    /**
     * Manages assets (CSS, JS and images).
     *
     * @param string     $path    File path (relative from static/ dir).
     * @param array|null $options
     *
     * @return Asset
     */
    public function asset(string $path, array $options = null): Asset
    {
        return new Asset($this->builder, $path, $options);
    }

    /**
     * Minifying an asset (CSS or JS).
     * ie: minify('css/style.css').
     *
     * @param string|Asset $asset
     *
     * @throws Exception
     *
     * @return Asset
     */
    public function minify($asset): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        $oldPath = $asset['path'];
        $asset['path'] = \sprintf('%s.min.%s', substr($asset['path'], 0, -strlen('.'.$asset['ext'])), $asset['ext']);

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($asset);
        if (!$cache->has($cacheKey)) {
            switch ($asset['ext']) {
                case 'css':
                    $minifier = new Minify\CSS($asset['content']);
                    break;
                case 'js':
                    $minifier = new Minify\JS($asset['content']);
                    break;
                default:
                    throw new Exception(sprintf('%s() error: not able to process "%s"', __FUNCTION__, $asset));
            }
            $asset['content'] = $minifier->minify();
            $cache->set($cacheKey, $asset['content']);
        }
        $asset['content'] = $cache->get($cacheKey, $asset['content']);

        // save?
        if (!$this->builder->getBuildOptions()['dry-run']) {
            Util::getFS()->dumpFile(Util::joinFile($this->config->getOutputPath(), $asset['path']), $asset['content']);
            Util::getFS()->remove(Util::joinFile($this->config->getOutputPath(), $oldPath));
        }

        return $asset;
    }

    /**
     * Compiles a SCSS asset.
     *
     * @param string|Asset $asset
     *
     * @throws Exception
     *
     * @return Asset
     */
    public function toCss($asset): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        if ($asset['ext'] != 'scss') {
            throw new Exception(sprintf('%s() error: not able to process "%s"', __FUNCTION__, $asset));
        }

        $oldPath = $asset['path'];
        $asset['path'] = preg_replace('/scss/m', 'css', $asset['path']);
        $asset['ext'] = 'css';

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromAsset($asset);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            $variables = $this->config->get('assets.sass.variables') ?? [];
            $scssDir = $this->config->get('assets.sass.dir') ?? [];
            $themes = $this->config->getTheme() ?? [];
            foreach ($scssDir as $dir) {
                $scssPhp->addImportPath(Util::joinPath($this->config->getStaticPath(), $dir));
                $scssPhp->addImportPath(Util::joinPath(dirname($asset['file']), $dir));
                foreach ($themes as $theme) {
                    $scssPhp->addImportPath(Util::joinPath($this->config->getThemeDirPath($theme, "static/$dir")));
                }
            }
            $scssPhp->setVariables($variables);
            $scssPhp->setFormatter('ScssPhp\ScssPhp\Formatter\\'.ucfirst($this->config->get('assets.sass.style')));
            $asset['content'] = $scssPhp->compile($asset['content']);
            $cache->set($cacheKey, $asset['content']);
        }

        $asset['content'] = $cache->get($cacheKey, $asset['content']);

        // save?
        if (!$this->builder->getBuildOptions()['dry-run']) {
            Util::getFS()->dumpFile(Util::joinFile($this->config->getOutputPath(), $asset['path']), $asset['content']);
            Util::getFS()->remove(Util::joinFile($this->config->getOutputPath(), $oldPath));
        }

        return $asset;
    }

    /**
     * Resizes an image.
     *
     * @param string $path Image path (relative from static/ dir or external).
     * @param int    $size Image new size (width).
     *
     * @return string
     */
    public function resize(string $path, int $size): string
    {
        return (new Image($this->builder))->resize($path, $size);
    }

    /**
     * Hashing an asset with sha384.
     * Useful for SRI (Subresource Integrity).
     *
     * @see https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity
     *
     * @param string|Asset $path
     *
     * @return string|null
     */
    public function hashFile($asset): ?string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return sprintf('sha384-%s', base64_encode(hash('sha384', $asset['content'], true)));
    }

    /**
     * Minifying a CSS string.
     *
     * @param string $value
     *
     * @return string
     */
    public function minifyCss(string $value): string
    {
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue($value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\CSS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Minifying a JavaScript string.
     *
     * @param string $value
     *
     * @return string
     */
    public function minifyJs(string $value): string
    {
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue($value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\JS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Compiles a SCSS string.
     *
     * @param string $value
     *
     * @return string
     */
    public function scssToCss(string $value): string
    {
        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue($value);
        if (!$cache->has($cacheKey)) {
            $scss = new Compiler();
            $value = $scss->compile($value);
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Creates an HTML element from an asset.
     *
     * @return string
     */
    public function createHtmlElement(Asset $asset): string
    {
        if ($asset['type'] == 'image') {
            $title = array_key_exists('title', $asset['attributs']) ? $asset['attributs']['title'] : null;
            $alt = array_key_exists('alt', $asset['attributs']) ? $asset['attributs']['alt'] : null;

            return \sprintf(
                '<img src="%s"%s%s>',
                $asset['path'],
                !is_null($title) ? \sprintf(' title="%s"', $title) : '',
                !is_null($alt) ? \sprintf(' alt="%s"', $alt) : ''
            );
        }

        switch ($asset['ext']) {
            case 'css':
                return \sprintf('<link rel="stylesheet" href="%s">', $asset['path']);
            case 'js':
                return \sprintf('<script src="%s"></script>', $asset['path']);
        }

        throw new Exception(\sprintf('%s is available with CSS, JS and images files only.', '"html" filter'));
    }

    /**
     * Returns the content of an Asset.
     *
     * @param Asset $asset
     *
     * @return string
     */
    public function getContent(Asset $asset): string
    {
        if (is_null($asset['content'])) {
            throw new Exception(\sprintf('%s is available with CSS et JS files only.', '"inline" filter'));
        }

        return $asset['content'];
    }

    /**
     * Reads $length first characters of a string and adds a suffix.
     *
     * @param string|null $string
     * @param int         $length
     * @param string      $suffix
     *
     * @return string|null
     */
    public function excerpt(string $string = null, int $length = 450, string $suffix = ' …'): ?string
    {
        $string = str_replace('</p>', '<br /><br />', $string);
        $string = trim(strip_tags($string, '<br>'), '<br />');
        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Reads characters before '<!-- excerpt|break -->'.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public function excerptHtml(string $string = null): ?string
    {
        // https://regex101.com/r/Xl7d5I/3
        $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
        preg_match('/'.$pattern.'/is', $string, $matches);
        if (empty($matches)) {
            return $string;
        }

        return trim($matches[1]);
    }

    /**
     * Calculates estimated time to read a text.
     *
     * @param string|null $text
     *
     * @return string
     */
    public function readtime(string $text = null): string
    {
        $words = str_word_count(strip_tags($text));
        $min = floor($words / 200);
        if ($min === 0) {
            return '1';
        }

        return (string) $min;
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $var
     *
     * @return string|null
     */
    public function getEnv(string $var): ?string
    {
        return getenv($var) ?: null;
    }
}
