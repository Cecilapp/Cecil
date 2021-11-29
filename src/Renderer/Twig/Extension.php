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
            new \Twig\TwigFilter('fingerprint', [$this, 'fingerprint']),
            new \Twig\TwigFilter('to_css', [$this, 'toCss']),
            new \Twig\TwigFilter('minify', [$this, 'minify']),
            new \Twig\TwigFilter('minify_css', [$this, 'minifyCss']),
            new \Twig\TwigFilter('minify_js', [$this, 'minifyJs']),
            new \Twig\TwigFilter('scss_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('sass_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('resize', [$this, 'resize']),
            new \Twig\TwigFilter('dataurl', [$this, 'dataurl']),
            // content
            new \Twig\TwigFilter('slugify', [$this, 'slugifyFilter']),
            new \Twig\TwigFilter('excerpt', [$this, 'excerpt']),
            new \Twig\TwigFilter('excerpt_html', [$this, 'excerptHtml']),
            new \Twig\TwigFilter('markdown_to_html', [$this, 'markdownToHtml']),
            new \Twig\TwigFilter('json_decode', [$this, 'jsonDecode']),
            new \Twig\TwigFilter('preg_split', [$this, 'pregSplit']),
            new \Twig\TwigFilter('preg_match_all', [$this, 'pregMatchAll']),
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
     */
    public function filterBySection(PagesCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filters by variable's name/value.
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
     */
    public function sortByTitle(\Traversable $collection): array
    {
        $collection = iterator_to_array($collection);
        array_multisort(array_keys($collection), SORT_NATURAL | SORT_FLAG_CASE, $collection);

        return $collection;
    }

    /**
     * Sorts by weight.
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
     * Minifying an asset (CSS or JS).
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
     * Fingerprinting an asset.
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function fingerprint($asset): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->fingerprint();
    }

    /**
     * Resizes an image.
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function resize($asset, int $size): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->resize($size);
    }

    /**
     * Returns the data URL of an image.
     *
     * @param string|Asset $asset
     *
     * @return string
     */
    public function dataurl($asset): string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->dataurl();
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
     */
    public function minifyCss(string $value): string
    {
        if ($this->builder->isDebug()) {
            return $value;
        }

        $cache = new Cache($this->builder);
        $cacheKey = $cache->createKeyFromString($value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\CSS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Minifying a JavaScript string.
     */
    public function minifyJs(string $value): string
    {
        if ($this->builder->isDebug()) {
            return $value;
        }

        $cache = new Cache($this->builder);
        $cacheKey = $cache->createKeyFromString($value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\JS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Compiles a SCSS string.
     */
    public function scssToCss(string $value): string
    {
        $cache = new Cache($this->builder);
        $cacheKey = $cache->createKeyFromString($value);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!in_array($outputStyle, $outputStyles)) {
                throw new Exception(\sprintf('Scss output style "%s" doesn\'t exists.', $outputStyle));
            }
            $scssPhp->setOutputStyle($outputStyle);
            $variables = $this->config->get('assets.compile.variables') ?? [];
            if (!empty($variables)) {
                $variables = array_map('ScssPhp\ScssPhp\ValueConverter::parseValue', $variables);
                $scssPhp->replaceVariables($variables);
            }
            $value = $scssPhp->compileString($value)->getCss();
            $cache->set($cacheKey, $value);
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Returns the HTML version of an asset.
     *
     * $options[
     *     'preload'    => false,
     *     'responsive' => false,
     * ];
     */
    public function html(Asset $asset, array $attributes = null, array $options = null): string
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
                if ($options['preload']) {
                    return \sprintf(
                        '<link href="%s" rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"%s>
                         <noscript><link rel="stylesheet" href="%1$s"%2$s></noscript>',
                        $this->url($asset['path'], $options),
                        $htmlAttributes
                    );
                }

                return \sprintf('<link rel="stylesheet" href="%s"%s>', $this->url($asset['path'], $options), $htmlAttributes);
            case 'js':
                return \sprintf('<script src="%s"%s></script>', $this->url($asset['path'], $options), $htmlAttributes);
        }

        if ($asset['type'] == 'image') {
            if ($options['responsive']) {
                if ($srcset = Image::getSrcset(
                    $asset,
                    $this->builder->getConfig()->get('body.images.responsive.width.steps') ?? 5,
                    $this->builder->getConfig()->get('body.images.responsive.width.min') ?? 320,
                    $this->builder->getConfig()->get('body.images.responsive.width.max') ?? 1280
                )) {
                    $htmlAttributes .= \sprintf(' srcset="%s"', $srcset);
                    $htmlAttributes .= \sprintf(' sizes="%s"', '100vw');
                }
            }

            return \sprintf(
                '<img src="%s" width="'. $asset->getWidth() .'" height="'. $asset->getHeight() .'"%s>',
                $this->url($asset['path'], $options),
                $htmlAttributes
            );
        }

        throw new Exception(\sprintf('%s is available with CSS, JS and images files only.', '"html" filter'));
    }

    /**
     * Returns the content of an asset.
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
     */
    public function excerptHtml(string $string): string
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
     * Split a string into an array using a regular expression.
     */
    public function pregSplit(string $value, string $pattern, int $limit = 0): ?array
    {
        try {
            $array = preg_split($pattern, $value, $limit);
            if ($array === false) {
                throw new \Exception('Error');
            }
        } catch (\Exception $e) {
            throw new Exception('"preg_split" filter can not split supplied string.');
        }

        return $array;
    }

    /**
     * Perform a regular expression match and return the group for all matches.
     */
    public function pregMatchAll(string $value, string $pattern, int $group = 0): ?array
    {
        try {
            $array = preg_match_all($pattern, $value, $matches, PREG_PATTERN_ORDER);
            if ($array === false) {
                throw new \Exception('Error');
            }
        } catch (\Exception $e) {
            throw new Exception('"preg_match_all" filter can not match in supplied string.');
        }

        return $matches[$group];
    }

    /**
     * Calculates estimated time to read a text.
     */
    public function readtime(string $text): string
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
     */
    public function getEnv(string $var): ?string
    {
        return getenv($var) ?: null;
    }
}
