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

namespace Cecil\Renderer\Extension;

use Cecil\Asset;
use Cecil\Asset\Image;
use Cecil\Builder;
use Cecil\Cache;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Config;
use Cecil\Converter\Parsedown;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Url;
use Cecil\Util;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use Highlight\Highlighter;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Twig\DeprecatedCallableInfo;

/**
 * Core Twig extension.
 *
 * This extension provides various utility functions and filters for use in Twig templates,
 * including URL generation, asset management, content processing, and more.
 */
class Core extends SlugifyExtension
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
        $this->config = $builder->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'CoreExtension';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('url', [$this, 'url'], ['needs_context' => true]),
            // assets
            new \Twig\TwigFunction('asset', [$this, 'asset']),
            new \Twig\TwigFunction('html', [$this, 'html'], ['needs_context' => true]),
            new \Twig\TwigFunction('css', [$this, 'htmlCss'], ['needs_context' => true]),
            new \Twig\TwigFunction('js', [$this, 'htmlJs'], ['needs_context' => true]),
            new \Twig\TwigFunction('image', [$this, 'htmlImage'], ['needs_context' => true]),
            new \Twig\TwigFunction('audio', [$this, 'htmlAudio'], ['needs_context' => true]),
            new \Twig\TwigFunction('video', [$this, 'htmlVideo'], ['needs_context' => true]),
            new \Twig\TwigFunction('integrity', [$this, 'integrity']),
            new \Twig\TwigFunction('image_srcset', [$this, 'imageSrcset']),
            new \Twig\TwigFunction('image_sizes', [$this, 'imageSizes']),
            new \Twig\TwigFunction('image_from_website', [$this, 'htmlImageFromWebsite'], ['needs_context' => true]),
            // content
            new \Twig\TwigFunction('readtime', [$this, 'readtime']),
            new \Twig\TwigFunction('hash', [$this, 'hash']),
            // others
            new \Twig\TwigFunction('getenv', [$this, 'getEnv']),
            new \Twig\TwigFunction('d', [$this, 'varDump'], ['needs_context' => true, 'needs_environment' => true]),
            // deprecated
            new \Twig\TwigFunction(
                'minify',
                [$this, 'minify'],
                ['deprecation_info' => new DeprecatedCallableInfo('', '', 'minify filter')]
            ),
            new \Twig\TwigFunction(
                'toCSS',
                [$this, 'toCss'],
                ['deprecation_info' => new DeprecatedCallableInfo('', '', 'to_css filter')]
            ),
            new \Twig\TwigFunction(
                'image_from_url',
                [$this, 'htmlImageFromWebsite'],
                ['deprecation_info' => new DeprecatedCallableInfo('', '', 'image_from_website function')]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('url', [$this, 'url'], ['needs_context' => true]),
            // collections
            new \Twig\TwigFilter('sort_by_title', [$this, 'sortByTitle']),
            new \Twig\TwigFilter('sort_by_weight', [$this, 'sortByWeight']),
            new \Twig\TwigFilter('sort_by_date', [$this, 'sortByDate']),
            new \Twig\TwigFilter('filter_by', [$this, 'filterBy']),
            // assets
            new \Twig\TwigFilter('inline', [$this, 'inline']),
            new \Twig\TwigFilter('fingerprint', [$this, 'fingerprint']),
            new \Twig\TwigFilter('to_css', [$this, 'toCss']),
            new \Twig\TwigFilter('minify', [$this, 'minify']),
            new \Twig\TwigFilter('minify_css', [$this, 'minifyCss']),
            new \Twig\TwigFilter('minify_js', [$this, 'minifyJs']),
            new \Twig\TwigFilter('scss_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('sass_to_css', [$this, 'scssToCss']),
            new \Twig\TwigFilter('resize', [$this, 'resize']),
            new \Twig\TwigFilter('maskable', [$this, 'maskable']),
            new \Twig\TwigFilter('dataurl', [$this, 'dataurl']),
            new \Twig\TwigFilter('dominant_color', [$this, 'dominantColor']),
            new \Twig\TwigFilter('lqip', [$this, 'lqip']),
            new \Twig\TwigFilter('webp', [$this, 'webp']),
            new \Twig\TwigFilter('avif', [$this, 'avif']),
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
            new \Twig\TwigFilter('splitline', [$this, 'splitLine']),
            new \Twig\TwigFilter('iterable', [$this, 'iterable']),
            new \Twig\TwigFilter('highlight', [$this, 'highlight']),
            new \Twig\TwigFilter('unique', [$this, 'unique']),
            // date
            new \Twig\TwigFilter('duration_to_iso8601', ['\Cecil\Util\Date', 'durationToIso8601']),
            // deprecated
            new \Twig\TwigFilter(
                'html',
                [$this, 'html'],
                [
                    'needs_context' => true,
                    'deprecation_info' => new DeprecatedCallableInfo('', '', 'html function')
                ]
            ),
            new \Twig\TwigFilter(
                'cover',
                [$this, 'resize'],
                [
                    'needs_context' => true,
                    'deprecation_info' => new DeprecatedCallableInfo('', '', 'resize filter')
                ]
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
            new \Twig\TwigTest('image_large', [$this, 'isImageLarge']),
            new \Twig\TwigTest('image_square', [$this, 'isImageSquare']),
        ];
    }

    /**
     * Filters by Section.
     */
    public function filterBySection(PagesCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filters a pages collection by variable's name/value.
     */
    public function filterBy(PagesCollection $pages, string $variable, string $value): CollectionInterface
    {
        $filteredPages = $pages->filter(function (Page $page) use ($variable, $value) {
            // is a dedicated getter exists?
            $method = 'get' . ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return $page->getType() == Type::PAGE->value && !$page->isVirtual() && true;
            }
            // or a classic variable
            if ($page->getVariable($variable) == $value) {
                return $page->getType() == Type::PAGE->value && !$page->isVirtual() && true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sorts a collection by title.
     */
    public function sortByTitle(\Traversable $collection): array
    {
        $sort = \SORT_ASC;

        $collection = iterator_to_array($collection);
        array_multisort(array_keys(/** @scrutinizer ignore-type */ $collection), $sort, \SORT_NATURAL | \SORT_FLAG_CASE, $collection);

        return $collection;
    }

    /**
     * Sorts a collection by weight.
     *
     * @param \Traversable|array $collection
     */
    public function sortByWeight($collection): array
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

            return $a['weight'] < $b['weight'] ? -1 : 1;
        };

        if (!\is_array($collection)) {
            $collection = iterator_to_array($collection);
        }
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

        return $collection;
    }

    /**
     * Sorts by creation date (or 'updated' date): the most recent first.
     */
    public function sortByDate(\Traversable $collection, string $variable = 'date', bool $descTitle = false): array
    {
        $callback = function ($a, $b) use ($variable, $descTitle) {
            if ($a[$variable] == $b[$variable]) {
                // if dates are equal and "descTitle" is true
                if ($descTitle && (isset($a['title']) && isset($b['title']))) {
                    return strnatcmp($b['title'], $a['title']);
                }

                return 0;
            }

            return $a[$variable] > $b[$variable] ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

        return $collection;
    }

    /**
     * Creates an URL.
     *
     * $options[
     *     'canonical' => false,
     *     'format'    => 'html',
     *     'language'  => null,
     * ];
     *
     * @param array                  $context
     * @param Page|Asset|string|null $value
     * @param array|null             $options
     */
    public function url(array $context, $value = null, ?array $options = null): string
    {
        $optionsLang = [];
        $optionsLang['language'] = (string) $context['site']['language'];
        $options = array_merge($optionsLang, $options ?? []);

        return (new Url($this->builder, $value, $options))->getUrl();
    }

    /**
     * Creates an Asset (CSS, JS, images, etc.) from a path or an array of paths.
     *
     * @param string|array $path    File path or array of files path (relative from `assets/` or `static/` dir).
     * @param array|null   $options
     *
     * @return Asset
     */
    public function asset($path, array|null $options = null): Asset
    {
        if (!\is_string($path) && !\is_array($path)) {
            throw new RuntimeException(\sprintf('Argument of "%s()" must a string or an array.', \Cecil\Util::formatMethodName(__METHOD__)));
        }

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
     * Resizes an image Asset to the given width or/and height.
     *
     * - If only the width is specified, the height is calculated to preserve the aspect ratio
     * - If only the height is specified, the width is calculated to preserve the aspect ratio
     * - If both width and height are specified, the image is resized to fit within the given dimensions, image is cropped and centered if necessary
     * - If remove_animation is true, any animation in the image (e.g., GIF) will be removed.
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function resize($asset, ?int $width = null, ?int $height = null, bool $remove_animation = false): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->resize(width: $width, height: $height, rmAnimation: $remove_animation);
    }

    /**
     * Creates a maskable icon from an image asset.
     * The maskable icon is used for Progressive Web Apps (PWAs).
     *
     * @param string|Asset $asset
     *
     * @return Asset
     */
    public function maskable($asset, ?int $padding = null): Asset
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->maskable($padding);
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
     * @param string|Asset $asset
     * @param string       $algo
     *
     * @return string
     */
    public function integrity($asset, string $algo = 'sha384'): string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return $asset->integrity($algo);
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

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue(null, $value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\CSS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value, $this->config->get('cache.assets.ttl'));
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

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue(null, $value);
        if (!$cache->has($cacheKey)) {
            $minifier = new Minify\JS($value);
            $value = $minifier->minify();
            $cache->set($cacheKey, $value, $this->config->get('cache.assets.ttl'));
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Compiles a SCSS string.
     *
     * @throws RuntimeException
     */
    public function scssToCss(?string $value): string
    {
        $value = $value ?? '';

        $cache = new Cache($this->builder, 'assets');
        $cacheKey = $cache->createKeyFromValue(null, $value);
        if (!$cache->has($cacheKey)) {
            $scssPhp = new Compiler();
            $outputStyles = ['expanded', 'compressed'];
            $outputStyle = strtolower((string) $this->config->get('assets.compile.style'));
            if (!\in_array($outputStyle, $outputStyles)) {
                throw new ConfigException(\sprintf('"%s" value must be "%s".', 'assets.compile.style', implode('" or "', $outputStyles)));
            }
            $scssPhp->setOutputStyle($outputStyle == 'compressed' ? OutputStyle::COMPRESSED : OutputStyle::EXPANDED);
            $variables = $this->config->get('assets.compile.variables');
            if (!empty($variables)) {
                $variables = array_map('ScssPhp\ScssPhp\ValueConverter::parseValue', $variables);
                $scssPhp->replaceVariables($variables);
            }
            $value = $scssPhp->compileString($value)->getCss();
            $cache->set($cacheKey, $value, $this->config->get('cache.assets.ttl'));
        }

        return $cache->get($cacheKey, $value);
    }

    /**
     * Creates the HTML element of an asset.
     *
     * @param array                                                                $context    Twig context
     * @param Asset|array<int,array{asset:Asset,attributes:?array<string,string>}> $assets     Asset or array of assets + attributes
     * @param array                                                                $attributes HTML attributes to add to the element
     * @param array                                                                $options    Options:
     * [
     *     'preload'    => false,
     *     'responsive' => false,
     *     'formats'    => [],
     * ];
     *
     * @return string HTML element
     *
     * @throws RuntimeException
     */
    public function html(array $context, Asset|array $assets, array $attributes = [], array $options = []): string
    {
        $html = array();
        if (!\is_array($assets)) {
            $assets = [['asset' => $assets, 'attributes' => null]];
        }
        foreach ($assets as $assetData) {
            $asset = $assetData['asset'];
            if (!$asset instanceof Asset) {
                $asset = new Asset($this->builder, $asset);
            }
            // be sure Asset file is saved
            $asset->save();
            // merge attributes
            $attr = $attributes;
            if ($assetData['attributes'] !== null) {
                $attr = $attributes + $assetData['attributes'];
            }
            // process by extension
            $attributes['as'] = $asset['type'];
            switch ($asset['ext']) {
                case 'css':
                    $html[] = $this->htmlCss($context, $asset, $attr, $options);
                    $attributes['as'] = 'style';
                    unset($attributes['defer']);
                    break;
                case 'js':
                    $html[] = $this->htmlJs($context, $asset, $attr, $options);
                    $attributes['as'] = $asset['script'];
                    break;
            }
            // process by MIME type
            switch ($asset['type']) {
                case 'image':
                    $html[] = $this->htmlImage($context, $asset, $attr, $options);
                    break;
                case 'audio':
                    $html[] = $this->htmlAudio($context, $asset, $attr, $options);
                    break;
                case 'video':
                    $html[] = $this->htmlVideo($context, $asset, $attr, $options);
                    break;
            }
            // preload
            if ($options['preload'] ?? false) {
                $attributes['type'] = $asset['subtype'];
                if (empty($attributes['crossorigin'])) {
                    $attributes['crossorigin'] = 'anonymous';
                }
                array_unshift($html, \sprintf('<link rel="preload" href="%s"%s>', $this->url($context, $asset, $options), self::htmlAttributes($attributes)));
            }
            unset($attr);
        }
        if (empty($html)) {
            throw new RuntimeException(\sprintf('%s failed to generate HTML element(s) for file(s) provided.', '"html" function'));
        }

        return implode("\n    ", $html);
    }

    /**
     * Builds the HTML link element of a CSS Asset.
     */
    public function htmlCss(array $context, Asset $asset, array $attributes = [], array $options = []): string
    {
        // simulate "defer" by using "preload" and "onload"
        if (isset($attributes['defer'])) {
            unset($attributes['defer']);
            return \sprintf(
                '<link rel="preload" href="%s" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"%s><noscript><link rel="stylesheet" href="%1$s"%2$s></noscript>',
                $this->url($context, $asset, $options),
                self::htmlAttributes($attributes)
            );
        }

        return \sprintf('<link rel="stylesheet" href="%s"%s>', $this->url($context, $asset, $options), self::htmlAttributes($attributes));
    }

    /**
     * Builds the HTML script element of a JS Asset.
     */
    public function htmlJs(array $context, Asset $asset, array $attributes = [], array $options = []): string
    {
        return \sprintf('<script src="%s"%s></script>', $this->url($context, $asset, $options), self::htmlAttributes($attributes));
    }

    /**
     * Builds the HTML img element of an image Asset.
     */
    public function htmlImage(array $context, Asset $asset, array $attributes = [], array $options = []): string
    {
        $responsive = $options['responsive'] ?? $this->config->get('layouts.images.responsive');

        // build responsive attributes
        try {
            if ($responsive === true || $responsive == 'width') {
                $srcset = Image::buildHtmlSrcsetW($asset, $this->config->getAssetsImagesWidths());
                if (!empty($srcset)) {
                    $attributes['srcset'] = $srcset;
                }
                $attributes['sizes'] = Image::getHtmlSizes($attributes['class'] ?? '', $this->config->getAssetsImagesSizes());
                // prevent oversized images
                if ($asset['width'] > max($this->config->getAssetsImagesWidths())) {
                    $asset = $asset->resize(max($this->config->getAssetsImagesWidths()));
                }
            } elseif ($responsive == 'density') {
                $width1x = isset($attributes['width']) && $attributes['width'] > 0 ? (int) $attributes['width'] : $asset['width'];
                $srcset = Image::buildHtmlSrcsetX($asset, $width1x, $this->config->getAssetsImagesDensities());
                if (!empty($srcset)) {
                    $attributes['srcset'] = $srcset;
                }
            }
        } catch (\Exception $e) {
            $this->builder->getLogger()->warning($e->getMessage());
        }

        // create alternative formats (`<source>`)
        try {
            $formats = $options['formats'] ?? (array) $this->config->get('layouts.images.formats');
            if (\count($formats) > 0) {
                $source = '';
                foreach ($formats as $format) {
                    try {
                        $assetConverted = $asset->convert($format);
                        // responsive
                        if ($responsive === true || $responsive == 'width') {
                            $srcset = Image::buildHtmlSrcsetW($assetConverted, $this->config->getAssetsImagesWidths());
                            if (empty($srcset)) {
                                $source .= \sprintf("\n  <source type=\"image/$format\" srcset=\"%s\">", (string) $assetConverted);
                                continue;
                            }
                            $source .= \sprintf("\n  <source type=\"image/$format\" srcset=\"%s\" sizes=\"%s\">", $srcset, Image::getHtmlSizes($attributes['class'] ?? '', $this->config->getAssetsImagesSizes()));
                            continue;
                        }
                        if ($responsive == 'density') {
                            $width1x = isset($attributes['width']) && $attributes['width'] > 0 ? (int) $attributes['width'] : $asset['width'];
                            $srcset = Image::buildHtmlSrcsetX($assetConverted, $width1x, $this->config->getAssetsImagesDensities());
                            if (empty($srcset)) {
                                $srcset = (string) $assetConverted;
                            }
                            $source .= \sprintf("\n  <source type=\"image/$format\" srcset=\"%s\">", $srcset);
                            continue;
                        }
                        $source .= \sprintf("\n  <source type=\"image/$format\" srcset=\"%s\">", $assetConverted);
                    } catch (\Exception $e) {
                        $this->builder->getLogger()->warning($e->getMessage());
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->builder->getLogger()->warning($e->getMessage());
        }

        // create `<img>` element
        if (!isset($attributes['alt'])) {
            $attributes['alt'] = '';
        }
        if (isset($attributes['width']) && $attributes['width'] > 0) {
            $asset = $asset->resize((int) $attributes['width']);
        }
        if (!isset($attributes['width'])) {
            $attributes['width'] = $asset['width'] ?: '';
        }
        if (!isset($attributes['height'])) {
            $attributes['height'] = $asset['height'] ?: '';
        }
        $img = \sprintf('<img src="%s"%s>', $this->url($context, $asset, $options), self::htmlAttributes($attributes));

        // put `<source>` elements in `<picture>` if exists
        if (!empty($source)) {
            return \sprintf("<picture>%s\n  %s\n</picture>", $source, $img);
        }

        return $img;
    }

    /**
     * Builds the HTML audio element of an audio Asset.
     */
    public function htmlAudio(array $context, Asset $asset, array $attributes = [], array $options = []): string
    {
        if (empty($attributes)) {
            $attributes['controls'] = '';
        }

        return \sprintf('<audio%s src="%s" type="%s"></audio>', self::htmlAttributes($attributes), $this->url($context, $asset, $options), $asset['subtype']);
    }

    /**
     * Builds the HTML video element of a video Asset.
     */
    public function htmlVideo(array $context, Asset $asset, array $attributes = [], array $options = []): string
    {
        if (empty($attributes)) {
            $attributes['controls'] = '';
        }

        return \sprintf('<video%s><source src="%s" type="%s"></video>', self::htmlAttributes($attributes), $this->url($context, $asset, $options), $asset['subtype']);
    }

    /**
     * Builds the HTML img `srcset` (responsive) attribute of an image Asset, based on configured widths.
     *
     * @throws RuntimeException
     */
    public function imageSrcset(Asset $asset): string
    {
        return Image::buildHtmlSrcsetW($asset, $this->config->getAssetsImagesWidths(), true);
    }

    /**
     * Returns the HTML img `sizes` attribute based on a CSS class name.
     */
    public function imageSizes(string $class): string
    {
        return Image::getHtmlSizes($class, $this->config->getAssetsImagesSizes());
    }

    /**
     * Builds the HTML img element from a website URL by extracting the image from meta tags.
     * Returns null if no image found.
     *
     * @todo enhance performance by caching results?
     *
     * @throws RuntimeException
     */
    public function htmlImageFromWebsite(array $context, string $url, array $attributes = [], array $options = []): ?string
    {
        if (false !== $html = Util\File::fileGetContents($url)) {
            $imageUrl = Util\Html::getImageFromMetaTags($html);
            if ($imageUrl !== null) {
                $asset = new Asset($this->builder, $imageUrl);

                return $this->htmlImage($context, $asset, $attributes, $options);
            }
        }

        return null;
    }

    /**
     * Converts an image Asset to WebP format.
     */
    public function webp(Asset $asset, ?int $quality = null): Asset
    {
        return $this->convert($asset, 'webp', $quality);
    }

    /**
     * Converts an image Asset to AVIF format.
     */
    public function avif(Asset $asset, ?int $quality = null): Asset
    {
        return $this->convert($asset, 'avif', $quality);
    }

    /**
     * Converts an image Asset to the given format.
     *
     * @throws RuntimeException
     */
    private function convert(Asset $asset, string $format, ?int $quality = null): Asset
    {
        if ($asset['subtype'] == "image/$format") {
            return $asset;
        }
        if (Image::isAnimatedGif($asset)) {
            throw new RuntimeException(\sprintf('Unable to convert the animated GIF "%s" to %s.', $asset['path'], $format));
        }

        try {
            return $asset->$format($quality);
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to convert "%s" to %s (%s).', $asset['path'], $format, $e->getMessage()));
        }
    }

    /**
     * Returns the content of an asset.
     */
    public function inline(Asset $asset): string
    {
        return $asset['content'];
    }

    /**
     * Reads $length first characters of a string and adds a suffix.
     */
    public function excerpt(?string $string, int $length = 450, string $suffix = ' …'): string
    {
        $string = $string ?? '';

        $string = str_replace('</p>', '<br><br>', $string);
        $string = trim(strip_tags($string, '<br>'));
        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Reads characters before or after '<!-- separator -->'.
     * Options:
     *  - separator: string to use as separator (`excerpt|break` by default)
     *  - capture: part to capture, `before` or `after` the separator (`before` by default).
     */
    public function excerptHtml(?string $string, array $options = []): string
    {
        $string = $string ?? '';

        $separator = (string) $this->config->get('pages.body.excerpt.separator');
        $capture = (string) $this->config->get('pages.body.excerpt.capture');
        extract($options, EXTR_IF_EXISTS);

        // https://regex101.com/r/n9TWHF/1
        $pattern = '(.*)<!--[[:blank:]]?(' . $separator . ')[[:blank:]]?-->(.*)';
        preg_match('/' . $pattern . '/is', $string, $matches);

        if (empty($matches)) {
            return $string;
        }
        $result = trim($matches[1]);
        if ($capture == 'after') {
            $result = trim($matches[3]);
        }
        // removes footnotes and returns result
        return preg_replace('/<sup[^>]*>[^u]*<\/sup>/', '', $result);
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
            throw new RuntimeException(
                '"markdown_to_html" filter can not convert supplied Markdown.',
                previous: $e
            );
        }

        return $html;
    }

    /**
     * Extracts only headings matching the given `selectors` (h2, h3, etc.),
     * or those defined in config `pages.body.toc` if not specified.
     * The `format` parameter defines the output format: `html` or `json`.
     * The `url` parameter is used to build links to headings.
     *
     * @throws RuntimeException
     */
    public function markdownToToc(?string $markdown, $format = 'html', ?array $selectors = null, string $url = ''): ?string
    {
        $markdown = $markdown ?? '';
        $selectors = $selectors ?? (array) $this->config->get('pages.body.toc');

        try {
            $parsedown = new Parsedown($this->builder, ['selectors' => $selectors, 'url' => $url]);
            $parsedown->body($markdown);
            $return = $parsedown->contentsList($format);
        } catch (\Exception) {
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
        } catch (\Exception) {
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
            $array = Yaml::parse($yaml, Yaml::PARSE_DATETIME);
            if (!\is_array($array)) {
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
        } catch (\Exception) {
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
        } catch (\Exception) {
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
     * Dump variable (or Twig context).
     */
    public function varDump(\Twig\Environment $env, array $context, $var = null, ?array $options = null): void
    {
        if (!$env->isDebug()) {
            return;
        }

        if ($var === null) {
            $var = array();
            foreach ($context as $key => $value) {
                if (!$value instanceof \Twig\Template && !$value instanceof \Twig\TemplateWrapper) {
                    $var[$key] = $value;
                }
            }
        }

        $cloner = new VarCloner();
        $cloner->setMinDepth(3);
        $dumper = new HtmlDumper();
        $dumper->setTheme($options['theme'] ?? 'light');

        $data = $cloner->cloneVar($var)->withMaxDepth(3);
        $dumper->dump($data, null, ['maxDepth' => 3]);
    }

    /**
     * Tests if a variable is an Asset.
     */
    public function isAsset($variable): bool
    {
        return $variable instanceof Asset;
    }

    /**
     * Tests if an image Asset is large enough to be used as a cover image.
     * A large image is defined as having a width >= 600px and height >= 315px.
     */
    public function isImageLarge(Asset $asset): bool
    {
        return $asset['type'] == 'image' && $asset['width'] > $asset['height'] && $asset['width'] >= 600 && $asset['height'] >= 315;
    }

    /**
     * Tests if an image Asset is square.
     * A square image is defined as having the same width and height.
     */
    public function isImageSquare(Asset $asset): bool
    {
        return $asset['type'] == 'image' && $asset['width'] == $asset['height'];
    }

    /**
     * Returns the dominant hex color of an image asset.
     *
     * @param string|Asset $asset
     *
     * @return string
     */
    public function dominantColor($asset): string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return Image::getDominantColor($asset);
    }

    /**
     * Returns a Low Quality Image Placeholder (LQIP) as data URL.
     *
     * @param string|Asset $asset
     *
     * @return string
     */
    public function lqip($asset): string
    {
        if (!$asset instanceof Asset) {
            $asset = new Asset($this->builder, $asset);
        }

        return Image::getLqip($asset);
    }

    /**
     * Converts an hexadecimal color to RGB.
     *
     * @throws RuntimeException
     */
    public function hexToRgb(?string $variable): array
    {
        $variable = $variable ?? '';

        if (!self::isHex($variable)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid hexadecimal value.', $variable));
        }
        $hex = ltrim($variable, '#');
        if (\strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $c = hexdec($hex);

        return [
            'red'   => $c >> 16 & 0xFF,
            'green' => $c >> 8 & 0xFF,
            'blue'  => $c & 0xFF,
        ];
    }

    /**
     * Split a string in multiple lines.
     */
    public function splitLine(?string $variable, int $max = 18): array
    {
        $variable = $variable ?? '';

        return preg_split("/.{0,{$max}}\K(\s+|$)/", $variable, 0, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Hashing an object, an array or a string (with algo, md5 by default).
     */
    public function hash(object|array|string $data, $algo = 'md5'): string
    {
        switch (\gettype($data)) {
            case 'object':
                return spl_object_hash($data);
            case 'array':
                return hash($algo, serialize($data));
        }

        return hash($algo, $data);
    }

    /**
     * Converts a variable to an iterable (array).
     */
    public function iterable($value): array
    {
        if (\is_array($value)) {
            return $value;
        }
        if (\is_string($value)) {
            return [$value];
        }
        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }
        if ($value instanceof \stdClass) {
            return (array) $value;
        }
        if (\is_object($value)) {
            return [$value];
        }
        if (\is_int($value) || \is_float($value)) {
            return [$value];
        }
        return [$value];
    }

    /**
     * Highlights a code snippet.
     */
    public function highlight(string $code, string $language): string
    {
        return (new Highlighter())->highlight($language, $code)->value;
    }

    /**
     * Returns an array with unique values.
     */
    public function unique(array $array): array
    {
        return array_intersect_key($array, array_unique(array_map('strtolower', $array), SORT_STRING));
    }

    /**
     * Is a hexadecimal color is valid?
     */
    private static function isHex(string $hex): bool
    {
        $valid = \is_string($hex);
        $hex = ltrim($hex, '#');
        $length = \strlen($hex);
        $valid = $valid && ($length === 3 || $length === 6);
        $valid = $valid && ctype_xdigit($hex);

        return $valid;
    }

    /**
     * Builds the HTML attributes string from an array.
     */
    private static function htmlAttributes(array $attributes): string
    {
        $htmlAttributes = '';
        foreach ($attributes as $name => $value) {
            $attribute = \sprintf(' %s="%s"', $name, $value);
            if (empty($value)) {
                $attribute = \sprintf(' %s', $name);
            }
            $htmlAttributes .= $attribute;
        }

        return $htmlAttributes;
    }

    /**
     * Override parent's slugifyFilter to add Chinese character support.
     */
    public function slugifyFilter(string $string, $separator = '-'): string
    {
        return self::$slugifier->slugify($string, [
            'separator' => $separator,
            'ruleset' => 'chinese',
        ]);
    }
}
