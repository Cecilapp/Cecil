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

namespace Cecil\Renderer\Twig;

use Cecil\Assets\Asset;
use Cecil\Assets\Cache;
use Cecil\Assets\Image;
use Cecil\Assets\Url;
use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Config;
use Cecil\Converter\Parsedown;
use Cecil\Exception\RuntimeException;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
            new \Twig\TwigFilter('toc', [$this, 'markdownToToc']),
            new \Twig\TwigFilter('json_decode', [$this, 'jsonDecode']),
            new \Twig\TwigFilter('yaml_parse', [$this, 'yamlParse']),
            new \Twig\TwigFilter('preg_split', [$this, 'pregSplit']),
            new \Twig\TwigFilter('preg_match_all', [$this, 'pregMatchAll']),
            new \Twig\TwigFilter('hex_to_rgb', [$this, 'hexToRgb']),
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
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new \Twig\TwigTest('asset', [$this, 'isAsset']),
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
            // is a dedicated getter exists?
            $method = 'get'.ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return $page->getType() == Type::PAGE() && !$page->isVirtual() && true;
            }
            // or a classic variable
            if ($page->getVariable($variable) == $value) {
                return $page->getType() == Type::PAGE() && !$page->isVirtual() && true;
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
        /** @var \array $collection */
        array_multisort(array_keys(/** @scrutinizer ignore-type */ $collection), \SORT_ASC, \SORT_NATURAL | \SORT_FLAG_CASE, $collection);

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
        /** @var \array $collection */
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

        return $collection;
    }

    /**
     * Sorts by creation date (or 'updated' date): the most recent first.
     */
    public function sortByDate(\Traversable $collection, string $variable = 'date'): array
    {
        $callback = function ($a, $b) use ($variable) {
            if ($a[$variable] == $b[$variable]) {
                return 0;
            }

            return ($a[$variable] > $b[$variable]) ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        /** @var \array $collection */
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

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
    public function minifyCss(?string $value): string
    {
        $value = $value ?? '';

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
    public function minifyJs(?string $value): string
    {
        $value = $value ?? '';

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
    public function scssToCss(?string $value): string
    {
        $value = $value ?? '';

        $cache = new Cache($this->builder);
        $cacheKey = $cache->createKeyFromString($value);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!in_array($outputStyle, $outputStyles)) {
                throw new RuntimeException(\sprintf('Scss output style "%s" doesn\'t exists.', $outputStyle));
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
     *
     * @throws RuntimeException
     */
    public function html(Asset $asset, array $attributes = [], array $options = []): string
    {
        $htmlAttributes = '';
        $preload = false;
        $responsive = $this->config->get('assets.images.responsive.enabled') ?? false;
        $webp = $this->config->get('assets.images.webp.enabled') ?? false;
        extract($options, EXTR_IF_EXISTS);

        foreach ($attributes as $name => $value) {
            $attribute = \sprintf(' %s="%s"', $name, $value);
            if (empty($value)) {
                $attribute = \sprintf(' %s', $name);
            }
            $htmlAttributes .= $attribute;
        }

        $asset->save();

        /* CSS or JavaScript */
        switch ($asset['ext']) {
            case 'css':
                if ($preload) {
                    return \sprintf(
                        '<link href="%s" rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"%s><noscript><link rel="stylesheet" href="%1$s"%2$s></noscript>',
                        $this->url($asset, $options),
                        $htmlAttributes
                    );
                }

                return \sprintf('<link rel="stylesheet" href="%s"%s>', $this->url($asset, $options), $htmlAttributes);
            case 'js':
                return \sprintf('<script src="%s"%s></script>', $this->url($asset, $options), $htmlAttributes);
        }

        /* Image */
        if ($asset['type'] == 'image') {
            // responsive
            if ($responsive && $srcset = Image::buildSrcset(
                $asset,
                $this->config->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
            )) {
                $htmlAttributes .= \sprintf(' srcset="%s"', $srcset);
                $htmlAttributes .= \sprintf(' sizes="%s"', $this->config->get('assets.images.responsive.sizes.default') ?? '100vw');
            }

            // <img>
            $img = \sprintf(
                '<img src="%s" width="'.($asset->getWidth() ?: 0).'" height="'.($asset->getHeight() ?: 0).'"%s>',
                $this->url($asset, $options),
                $htmlAttributes
            );

            // WebP transformation?
            if ($webp && !Image::isAnimatedGif($asset)) {
                try {
                    $assetWebp = Image::convertTopWebp($asset, $this->config->get('assets.images.quality') ?? 75);
                    // <source>
                    $source = \sprintf('<source type="image/webp" srcset="%s">', $assetWebp);
                    // responsive
                    if ($responsive) {
                        $srcset = Image::buildSrcset(
                            $assetWebp,
                            $this->config->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
                        ) ?: (string) $assetWebp;
                        // <source>
                        $source = \sprintf(
                            '<source type="image/webp" srcset="%s" sizes="%s">',
                            $srcset,
                            $this->config->get('assets.images.responsive.sizes.default') ?? '100vw'
                        );
                    }

                    return \sprintf("<picture>\n  %s\n  %s\n</picture>", $source, $img);
                } catch (\Exception $e) {
                    $this->builder->getLogger()->debug($e->getMessage());
                }
            }

            return $img;
        }

        throw new RuntimeException(\sprintf('%s is available with CSS, JS and images files only.', '"html" filter'));
    }

    /**
     * Returns the content of an asset.
     *
     * @throws RuntimeException
     */
    public function inline(Asset $asset): string
    {
        if (is_null($asset['content'])) {
            throw new RuntimeException(\sprintf('%s is available with CSS et JS files only.', '"inline" filter'));
        }

        return $asset['content'];
    }

    /**
     * Reads $length first characters of a string and adds a suffix.
     */
    public function excerpt(?string $string, int $length = 450, string $suffix = ' …'): string
    {
        $string = $string ?? '';

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
     * Options:
     *  - separator: string to use as separator
     *  - capture: string to capture, 'before' (default) or 'after'.
     */
    public function excerptHtml(?string $string, array $options = []): string
    {
        $string = $string ?? '';

        $separator = 'excerpt|break';
        $capture = 'before';
        extract($options, EXTR_IF_EXISTS);

        // https://regex101.com/r/n9TWHF/1
        $pattern = '(.*)<!--[[:blank:]]?('.$separator.')[[:blank:]]?-->(.*)';
        preg_match('/'.$pattern.'/is', $string, $matches);

        if (empty($matches)) {
            return $string;
        }
        if ($capture == 'after') {
            return trim($matches[3]);
        }
        // remove footnotes
        return preg_replace('/<sup[^>]*>[^u]*<\/sup>/', '', trim($matches[1]));
    }

    /**
     * Converts a Markdown string to HTML.
     *
     * @throws RuntimeException
     */
    public function markdownToHtml(?string $markdown): ?string
    {
        $markdown = $markdown ?? '';

        try {
            $parsedown = new Parsedown($this->builder);
            $html = $parsedown->text($markdown);
        } catch (\Exception $e) {
            throw new RuntimeException('"markdown_to_html" filter can not convert supplied Markdown.');
        }

        return $html;
    }

    /**
     * Extract table of content of a Markdown string,
     * in the given format ("html" or "json", "html" by default).
     */
    public function markdownToToc(?string $markdown, $format = 'html'): ?string
    {
        $markdown = $markdown ?? '';

        try {
            $parsedown = new Parsedown($this->builder, ['selectors' => ['h2']]);
            $parsedown->body($markdown);
            $return = $parsedown->contentsList($format);
        } catch (\Exception $e) {
            throw new RuntimeException('"toc" filter can not convert supplied Markdown.');
        }

        return $return;
    }

    /**
     * Converts a JSON string to an array.
     *
     * @throws RuntimeException
     */
    public function jsonDecode(?string $json): ?array
    {
        $json = $json ?? '';

        try {
            $array = json_decode($json, true);
            if ($array === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON error.');
            }
        } catch (\Exception $e) {
            throw new RuntimeException('"json_decode" filter can not parse supplied JSON.');
        }

        return $array;
    }

    /**
     * Converts a YAML string to an array.
     *
     * @throws RuntimeException
     */
    public function yamlParse(?string $yaml): ?array
    {
        $yaml = $yaml ?? '';

        try {
            $array = Yaml::parse($yaml);
            if (!is_array($array)) {
                throw new ParseException('YAML error.');
            }
        } catch (ParseException $e) {
            throw new RuntimeException(\sprintf('"yaml_parse" filter can not parse supplied YAML: %s', $e->getMessage()));
        }

        return $array;
    }

    /**
     * Split a string into an array using a regular expression.
     *
     * @throws RuntimeException
     */
    public function pregSplit(?string $value, string $pattern, int $limit = 0): ?array
    {
        $value = $value ?? '';

        try {
            $array = preg_split($pattern, $value, $limit);
            if ($array === false) {
                throw new RuntimeException('PREG split error.');
            }
        } catch (\Exception $e) {
            throw new RuntimeException('"preg_split" filter can not split supplied string.');
        }

        return $array;
    }

    /**
     * Perform a regular expression match and return the group for all matches.
     *
     * @throws RuntimeException
     */
    public function pregMatchAll(?string $value, string $pattern, int $group = 0): ?array
    {
        $value = $value ?? '';

        try {
            $array = preg_match_all($pattern, $value, $matches, PREG_PATTERN_ORDER);
            if ($array === false) {
                throw new RuntimeException('PREG match all error.');
            }
        } catch (\Exception $e) {
            throw new RuntimeException('"preg_match_all" filter can not match in supplied string.');
        }

        return $matches[$group];
    }

    /**
     * Calculates estimated time to read a text.
     */
    public function readtime(?string $text): string
    {
        $text = $text ?? '';

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
    public function getEnv(?string $var): ?string
    {
        $var = $var ?? '';

        return getenv($var) ?: null;
    }

    /**
     * Tests if a variable is an Asset.
     */
    public function isAsset($variable): bool
    {
        return $variable instanceof Asset;
    }

    /**
     * Converts an hexadecimal color to RGB.
     */
    public function hexToRgb(?string $variable): array
    {
        $variable = $variable ?? '';

        if (!self::isHex($variable)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid hexadecimal value.', $variable));
        }
        $hex = ltrim($variable, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $c = hexdec($hex);

        return [
            'red'   => $c >> 16 & 0xFF,
            'green' => $c >> 8 & 0xFF,
            'blue'  => $c & 0xFF,
        ];
    }

    /**
     * Is a hexadecimal color is valid?
     */
    private static function isHex(string $hex): bool
    {
        $valid = is_string($hex);
        $hex = ltrim($hex, '#');
        $length = strlen($hex);
        $valid = $valid && ($length === 3 || $length === 6);
        $valid = $valid && ctype_xdigit($hex);

        return $valid;
    }
}
