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
use Cecil\Assets\Url;
use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Converter\Parsedown;
use Cecil\Exception\Exception;
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
            new \Twig\TwigFilter('url', [$this, 'url']),
            new \Twig\TwigFilter('html', [$this, 'html']),
            new \Twig\TwigFilter('inline', [$this, 'inline']),
            new \Twig\TwigFilter('markdown_to_html', [$this, 'markdownToHtml']),
            new \Twig\TwigFilter('json_decode', [$this, 'jsonDecode']),
            new \Twig\TwigFilter('to_css', [$this, 'toCss']),
            new \Twig\TwigFilter('minify', [$this, 'minify']),
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
            new \Twig\TwigFilter(
                'filterBySection',
                [$this, 'filterBySection'],
                ['deprecated' => true, 'alternative' => 'filter_by']
            ),
            new \Twig\TwigFilter(
                'filterBy',
                [$this, 'filterBy'],
                ['deprecated' => true, 'alternative' => 'filter_by']
            ),
            new \Twig\TwigFilter(
                'sortByTitle',
                [$this, 'sortByTitle'],
                ['deprecated' => true, 'alternative' => 'sort_by_title']
            ),
            new \Twig\TwigFilter(
                'sortByWeight',
                [$this, 'sortByWeight'],
                ['deprecated' => true, 'alternative' => 'sort_by_weight']
            ),
            new \Twig\TwigFilter(
                'sortByDate',
                [$this, 'sortByDate'],
                ['deprecated' => true, 'alternative' => 'sort_by_date']
            ),
            new \Twig\TwigFilter(
                'minifyCSS',
                [$this, 'minifyCss'],
                ['deprecated' => true, 'alternative' => 'minifyCss']
            ),
            new \Twig\TwigFilter(
                'minifyJS',
                [$this, 'minifyJs'],
                ['deprecated' => true, 'alternative' => 'minifyJs']
            ),
            new \Twig\TwigFilter(
                'SCSStoCSS',
                [$this, 'scssToCss'],
                ['deprecated' => true, 'alternative' => 'scss_to_css']
            ),
            new \Twig\TwigFilter(
                'excerptHtml',
                [$this, 'excerptHtml'],
                ['deprecated' => true, 'alternative' => 'excerpt_html']
            ),
            new \Twig\TwigFilter(
                'urlize',
                [$this, 'slugifyFilter'],
                ['deprecated' => true, 'alternative' => 'slugify']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            // assets
            new \Twig\TwigFunction('url', [$this, 'url']),
            new \Twig\TwigFunction('asset', [$this, 'asset']),
            new \Twig\TwigFunction('integrity', [$this, 'integrity']),
            // content
            new \Twig\TwigFunction('readtime', [$this, 'readtime']),
            // others
            new \Twig\TwigFunction('getenv', [$this, 'getEnv']),
            // deprecated
            new \Twig\TwigFunction(
                'minify',
                [$this, 'minify'],
                ['deprecated' => true, 'alternative' => 'minify filter']
            ),
            new \Twig\TwigFunction(
                'toCSS',
                [$this, 'toCss'],
                ['deprecated' => true, 'alternative' => 'to_css filter']
            ),
            new \Twig\TwigFunction(
                'hash',
                [$this, 'integrity'],
                ['deprecated' => true, 'alternative' => 'integrity']
            ),
        ];
    }

    /**
     * Filters by Section.
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
                $a['weight'] = 0;
            }
            if (!isset($b['weight'])) {
                $a['weight'] = 0;
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
     * Sorts by date: the most recent first.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByDate(\Traversable $collection): array
    {
        $callback = function ($a, $b) {
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
    public function url($value = null, array $options = null)
    {
        return new Url($this->builder, $value, $options);
    }

    /**
     * Creates an asset (CSS, JS, images, etc.).
     *
     * @param string|array $path    File path (relative from static/ dir).
     * @param array|null   $options
     *
     * @return Asset
     */
    public function asset($path, array $options = null): Asset
    {
        return new Asset($this->builder, $path, $options);
    }

    /**
     * Minifying an asset (CSS or JS).
     * ie: minify('css/style.css').
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function minify($asset): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->minify();
    }

    /**
     * Compiles a SCSS asset.
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function toCss($asset): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->compile();
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
        return (new Image($this->builder))
            ->load($path)
            ->resize($size);
    }

    /**
     * Hashing an asset with algo (sha384 by default).
     *
     * @param string|Asset $path
     * @param string       $algo
     *
     * @return string
     */
    public function integrity($asset, string $algo = 'sha384'): string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->getIntegrity($algo);
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
            $scssPhp = new Compiler();
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!in_array($outputStyle, $outputStyles)) {
                throw new Exception(\sprintf('Scss output style "%s" doesn\'t exists.', $outputStyle));
            }
            $scssPhp->setOutputStyle($outputStyle);
            $scssPhp->setVariables($this->config->get('assets.compile.variables') ?? []);
            $value = $scssPhp->compile($value);
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Creates an HTML element from an asset.
     *
     * @param Asset      $asset
     * @param array|null $attributes
     *
     * @return string
     */
    public function html(Asset $asset, array $attributes = null): string
    {
        $htmlAttributes = '';
        foreach ($attributes as $name => $value) {
            if (!empty($value)) {
                $htmlAttributes .= \sprintf(' %s="%s"', $name, $value);
            } else {
                $htmlAttributes .= \sprintf(' %s', $name);
            }
        }

        switch ($asset['ext']) {
            case 'css':
                return \sprintf('<link rel="stylesheet" href="%s"%s>', $asset['path'], $htmlAttributes);
            case 'js':
                return \sprintf('<script src="%s"%%s></script>', $asset['path'], $htmlAttributes);
        }

        if ($asset['type'] == 'image') {
            return \sprintf(
                '<img src="%s"%s>',
                $asset['path'],
                $htmlAttributes
            );
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
    public function inline(Asset $asset): string
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
     * Converts a Markdown string to HTML.
     *
     * @param string|null $markdown
     *
     * @return string|null
     */
    public function markdownToHtml(string $markdown): ?string
    {
        try {
            $parsedown = new Parsedown($this->builder);
            $html = $parsedown->text($markdown);
        } catch (\Exception $e) {
            throw new Exception('"markdown_to_html" filter can not convert supplied Markdown.');
        }

        return $html;
    }

    /**
     * Converts a JSON string to an array.
     *
     * @param string|null $json
     *
     * @return array|null
     */
    public function jsonDecode(string $json): ?array
    {
        try {
            $array = json_decode($json, true);
            if ($array === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error');
            }
        } catch (\Exception $e) {
            throw new Exception('"json_decode" filter can not parse supplied JSON.');
        }

        return $array;
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
