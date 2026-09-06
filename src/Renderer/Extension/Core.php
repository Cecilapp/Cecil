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
use Cecil\Config;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Url;
use Cecil\Util;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\DeprecatedCallableInfo;
use Twig\Extension\AbstractExtension;

/**
 * Core Twig extension.
 *
 * This extension provides various utility functions and filters for use in Twig templates,
 * including URL generation, asset management, content processing, and more.
 */
class Core extends AbstractExtension
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

    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('url', [$this, 'url'], ['needs_context' => true]),
            // assets
            new \Twig\TwigFunction('asset', [$this, 'asset']),
            new \Twig\TwigFunction('integrity', [$this, 'integrity']),
            new \Twig\TwigFunction('html', [$this, 'html'], ['needs_context' => true]),
            new \Twig\TwigFunction('css', [$this, 'htmlCss'], ['needs_context' => true]),
            new \Twig\TwigFunction('js', [$this, 'htmlJs'], ['needs_context' => true]),
            new \Twig\TwigFunction('image', [$this, 'htmlImage'], ['needs_context' => true]),
            new \Twig\TwigFunction('audio', [$this, 'htmlAudio'], ['needs_context' => true]),
            new \Twig\TwigFunction('video', [$this, 'htmlVideo'], ['needs_context' => true]),
            new \Twig\TwigFunction('image_srcset', [$this, 'imageSrcset']),
            new \Twig\TwigFunction('image_sizes', [$this, 'imageSizes']),
            new \Twig\TwigFunction('image_from_website', [$this, 'htmlImageFromWebsite'], ['needs_context' => true]),
            // utilities
            new \Twig\TwigFunction('hash', [$this, 'hash']),
            new \Twig\TwigFunction('cache_key', [$this, 'cacheKey'], ['needs_context' => true]),
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

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('url', [$this, 'url'], ['needs_context' => true]),
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

    public function getTests()
    {
        return [
            new \Twig\TwigTest('asset', [$this, 'isAsset']),
            new \Twig\TwigTest('image_large', [$this, 'isImageLarge']),
            new \Twig\TwigTest('image_square', [$this, 'isImageSquare']),
        ];
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
     * @param array                                      $context
     * @param \Cecil\Collection\Page\Page|Asset|string|null $value
     * @param array|null                                 $options
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
        $cacheKey = $cache->createKey($value, name: 'css');
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
        $cacheKey = $cache->createKey($value, name: 'js');
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
        $cacheKey = $cache->createKey($value, name: 'css');
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
            $asset->save(); // be sure Asset file is saved
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
            // preload
            if ($options['preload'] ?? false) {
                $attributes['type'] = $asset['subtype'];
                if (empty($attributes['crossorigin'])) {
                    $attributes['crossorigin'] = 'anonymous';
                }
                $preloadLink = \sprintf('<link rel="preload" href="%s"%s>', $this->url($context, $asset, $options), self::htmlAttributes($attributes));
                // if image asset with a specified width, preload the right size
                if (null !== $width = isset($attributes['width']) && $attributes['width'] > 0 ? (int) $attributes['width'] : null) {
                    $preloadLink = \sprintf('<link rel="preload" href="%s"%s>', $this->url($context, $asset->resize($width), $options), self::htmlAttributes($attributes));
                }
                array_unshift($html, $preloadLink);
                // only CSS and JS can be preloaded this way
                if (!\in_array($asset['ext'], ['css', 'js'])) {
                    break;
                }
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
        $source = '';
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
        $formats = [];
        try {
            $formats = $options['formats'] ?? (array) $this->config->get('layouts.images.formats');
            if (\count($formats) > 0) {
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


        // dark color-scheme variant: auto-detect `{filename}{suffix}.{ext}` alongside the source image
        $darkSource = $this->buildDarkSourceHtml($asset, $formats, $responsive, $attributes);
        // mobile variant: auto-detect `{filename}{suffix}.{ext}` alongside the source image
        $mobileSource = $this->buildMobileSourceHtml($asset, $formats, $responsive, $attributes);

        // put `<source>` elements in `<picture>` if exists
        if (!empty($darkSource) || !empty($mobileSource) || !empty($source)) {
            return \sprintf("<picture>%s%s%s\n  %s\n</picture>", $darkSource, $mobileSource, $source, $img);
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
     * Builds HTML dark "source" elements for the dark color-scheme variant of an image Asset.
     *
     * @param array $formats    Alternative formats (e.g. ['avif', 'webp'])
     * @param mixed $responsive Responsive mode (true, 'width', 'density' or false)
     * @param array $attributes Image attributes
     */
    private function buildDarkSourceHtml(Asset $asset, array $formats, mixed $responsive, array $attributes): string
    {
        $darkSuffix = (string) $this->config->get('layouts.images.dark_suffix');
        $sizes = null;
        if ($responsive === true || $responsive === 'width') {
            $sizes = Image::getHtmlSizes($attributes['class'] ?? '', $this->config->getAssetsImagesSizes());
        }
        $darkSourceAttributes = Image::buildDarkSourceAttributes(
            $this->builder,
            $asset,
            $darkSuffix,
            $formats,
            [
                'responsive' => $responsive,
                'widths' => $this->config->getAssetsImagesWidths(),
                'densities' => $this->config->getAssetsImagesDensities(),
                'sizes' => $sizes,
                'width1x' => isset($attributes['width']) && $attributes['width'] > 0 ? (int) $attributes['width'] : null,
            ]
        );
        if (empty($darkSourceAttributes)) {
            return '';
        }
        $darkSource = '';
        foreach ($darkSourceAttributes as $sourceAttributes) {
            $darkSource .= \sprintf("\n  <source%s>", self::htmlAttributes($sourceAttributes));
        }

        return $darkSource;
    }

    /**
     * Builds HTML mobile "source" elements for the mobile variant of an image Asset.
     *
     * @param array $formats    Alternative formats (e.g. ['avif', 'webp'])
     * @param mixed $responsive Responsive mode (true, 'width', 'density' or false)
     * @param array $attributes Image attributes
     */
    private function buildMobileSourceHtml(Asset $asset, array $formats, mixed $responsive, array $attributes): string
    {
        $mobileSuffix = (string) $this->config->get('layouts.images.mobile_suffix');
        $mobileMediaQuery = (string) $this->config->get('layouts.images.mobile_media_query');
        $sizes = null;
        if ($responsive === true || $responsive === 'width') {
            $sizes = Image::getHtmlSizes($attributes['class'] ?? '', $this->config->getAssetsImagesSizes());
        }
        $mobileSourceAttributes = Image::buildMobileSourceAttributes(
            $this->builder,
            $asset,
            $mobileSuffix,
            $formats,
            [
                'responsive' => $responsive,
                'widths' => $this->config->getAssetsImagesWidths(),
                'densities' => $this->config->getAssetsImagesDensities(),
                'sizes' => $sizes,
                'width1x' => isset($attributes['width']) && $attributes['width'] > 0 ? (int) $attributes['width'] : null,
                'media' => $mobileMediaQuery,
            ]
        );
        if (empty($mobileSourceAttributes)) {
            return '';
        }
        $mobileSource = '';
        foreach ($mobileSourceAttributes as $sourceAttributes) {
            $mobileSource .= \sprintf("\n  <source%s>", self::htmlAttributes($sourceAttributes));
        }

        return $mobileSource;
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
     * @throws RuntimeException
     */
    public function htmlImageFromWebsite(array $context, string $url, array $attributes = [], array $options = []): ?string
    {
        $htmlAsset = new Asset($this->builder, $url, ['ignore_missing' => true]);

        if ($htmlAsset->isMissing()) {
            $this->builder->getLogger()->warning(\sprintf('Unable to fetch "%s" to extract image.', $url));

            return null;
        }

        if (!empty($html = $htmlAsset['content'])) {
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
     * Hashing an object, an array or a string (with algo, xxh128 by default).
     */
    public function hash(object|array|string $data, $algo = 'xxh128'): string
    {
        switch (\gettype($data)) {
            case 'object':
                return hash($algo, $data::class . spl_object_id($data));
            case 'array':
                return hash($algo, serialize($data));
        }

        return hash($algo, $data);
    }

    /**
     * Builds a cache key from a variable.
     * The cache key is built from the name of the variable, its hash, the site language and build.
     *
     * @param array                    $context Twig context, used to get the site language and build.
     * @param string                   $name    Name of the variable to build the cache key from.
     * @param object|array|string|null $value   The variable to build the cache key from.
     */
    public function cacheKey(array $context, string $name, object|array|string|null $value = null): string
    {
        $key = $name . ($value ? '-' . $this->hash($value) : '');
        $key = $key . '-' . $context['site']['language'] . '-' . $context['site']['build'];

        return preg_replace('/[{}()\/\\\@:]/', '-', $key); // replace any of the reserved characters
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
}
