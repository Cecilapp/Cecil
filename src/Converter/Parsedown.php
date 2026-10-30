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

namespace Cecil\Converter;

use Cecil\Asset;
use Cecil\Asset\Image;
use Cecil\Builder;
use Cecil\Converter\Highlighter\HighlighterFactory;
use Cecil\Converter\Highlighter\HighlighterInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Renderer\Twig;
use Cecil\Url;
use Cecil\Util;

/**
 * Parsedown class.
 *
 * This class extends ParsedownExtra (and ParsedownToc) and provides methods to parse Markdown content
 * with additional features such as inline insertions, image handling, note blocks,
 * and code highlighting.
 *
 * @property array<string, list<string>> $InlineTypes
 * @property string $inlineMarkerList
 * @property list<string> $specialCharacters
 * @property array<string, list<string>> $BlockTypes
 *
 * @method string line(string $text, list<string> $nonNestables = [])
 */
class Parsedown extends \ParsedownToc
{
    /** @var Builder */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /**
     * Regex for attributes.
     * @var string
     */
    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

    /**
     * Regex for image block.
     * @var string
     */
    protected $regexImage = "~^!\[.*?\]\(.*?\)~";

    /** @var array<string, mixed> Stores image processing results to avoid redundant computations within a build. */
    protected static array $imageProcessingCache = [];

    /**
     * @var string|null Cache scope marker so static caches can be reset between builds.
     */
    protected static ?string $cacheBuildId = null;

    /** @var string|null */
    protected $language;

    /** Are the files of the body (images, video and audio) processed as assets? */
    protected bool $assets = true;

    /** @var (\Closure(string): string)|null URL of a local file of the body, when files are not processed as assets. */
    protected ?\Closure $assetUrl = null;

    /** @var list<string> Headings (e.g.: `h2`) with an anchor link. */
    protected array $anchors = [];

    /** Code highlighter, created on the first fenced code block. */
    protected ?HighlighterInterface $highlighter = null;

    /** Notes templates renderer, shared across all instances of a build. */
    protected static ?Twig $notesRenderer = null;

    /** @var array<string, bool> Notes templates existence, by note type. */
    protected static array $notesTemplates = [];

    /** Notes rendered with a template, used to build unique IDs. */
    protected int $notesCount = 0;

    /**
     * Options:
     * - `selectors`: headings of the table of contents (`pages.body.toc` by default);
     * - `language`: language of the content;
     * - `assets`: `false` to keep the files of the body (images, video and audio) as they are, instead of
     *   processing them as assets (located, resized, converted, copied): e.g. to convert a page outside of a build;
     * - `asset_url`: with `assets` set to `false`, a callable returning the URL of a local file from its path in the body.
     *
     * @param array<string, mixed>|null $options Parsedown options (e.g. 'selectors', 'language', 'assets', 'asset_url')
     */
    public function __construct(Builder $builder, ?array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();

        // "insert" line block: ++text++ -> <ins>text</ins>
        $this->InlineTypes['+'][] = 'Insert'; // @phpstan-ignore-line
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes)); // @phpstan-ignore-line
        $this->specialCharacters[] = '+'; // @phpstan-ignore-line

        // Image block (to avoid paragraph)
        $this->BlockTypes['!'][] = 'Image'; // @phpstan-ignore-line

        // "notes" block
        $this->BlockTypes[':'][] = 'Note';

        // headings anchors
        $anchors = $this->config->get('pages.body.anchors');
        $this->anchors = \is_array($anchors) ? array_values(array_map('strval', $anchors)) : [];

        // reset static caches when the build ID changes (e.g. watch mode with multiple builds)
        $currentBuildId = Builder::getBuildId();
        if (static::$cacheBuildId !== $currentBuildId) {
            static::$imageProcessingCache = [];
            static::$notesRenderer = null;
            static::$notesTemplates = [];
            static::$cacheBuildId = $currentBuildId;
        }

        // options
        $options = array_merge([
            'selectors' => (array) $this->config->get('pages.body.toc'),
            'language'  => null,
            'assets'    => true,
            'asset_url' => null,
        ], $options ?? []);
        $language = $options['language'];
        if (\is_scalar($language)) {
            $language = (string) $language;
            $this->language = $language === '' ? null : $language;
        } else {
            $this->language = null;
        }
        $this->assets = (bool) $options['assets'];
        $this->assetUrl = \is_callable($options['asset_url']) ? \Closure::fromCallable($options['asset_url']) : null;
        unset($options['language'], $options['assets'], $options['asset_url']);

        parent::__construct();
        parent::setOptions($options);
    }

    /**
     * Insert inline.
     * e.g.: ++text++ -> <ins>text</ins>.
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineInsert($Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return null;
        }

        if ($Excerpt['text'][1] === '+' && preg_match('/^\+\+(?=\S)(.+?)(?<=\S)\+\+/', $Excerpt['text'], $matches)) {
            return [
                'extent'  => \strlen($matches[0]),
                'element' => [
                    'name'    => 'ins',
                    'text'    => $matches[1],
                    'handler' => 'line',
                ],
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineLink($Excerpt)
    {
        $link = parent::inlineLink($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return null;
        }

        // Link to a page with "page:page_id" as URL
        if (Util\Str::startsWith((string) $link['element']['attributes']['href'], 'page:')) {
            $link['element']['attributes']['href'] = new Url($this->builder, substr((string) $link['element']['attributes']['href'], 5, \strlen((string) $link['element']['attributes']['href'])));

            return $link;
        }

        // Handle external link
        $link = $this->handleExternalLink($link);

        /*
         * Embed enabled or embed attribute set?
         */
        $embed = $this->config->isEnabled('pages.body.links.embed');
        if (isset($link['element']['attributes']['embed'])) {
            $embed = true;
            if ($link['element']['attributes']['embed'] == 'false') {
                $embed = false;
            }
            unset($link['element']['attributes']['embed']);
        }

        /*
         * Local video and audio link
         */
        $extension = pathinfo((string) $link['element']['attributes']['href'], \PATHINFO_EXTENSION);
        // video
        if (\in_array($extension, $this->config->get('pages.body.links.embed.video') ?? [])) {
            if (!$embed) {
                $link['element']['attributes']['href'] = $this->getMediaLinkUrl((string) $link['element']['attributes']['href']);

                return $link;
            }
            $video = $this->createMediaFromLink($link, 'video');

            return $this->createFigure($video);
        }
        // audio
        if (\in_array($extension, $this->config->get('pages.body.links.embed.audio') ?? [])) {
            if (!$embed) {
                $link['element']['attributes']['href'] = $this->getMediaLinkUrl((string) $link['element']['attributes']['href']);

                return $link;
            }
            $audio = $this->createMediaFromLink($link, 'audio');

            return $this->createFigure($audio);
        }

        /*
         * Embed link to a service resource?
         * e.g.: YouTube, Vimeo, Dailymotion, GitHub Gist.
         */
        if ($embed && false !== $matches = Util::matchesUrlPattern((string) $link['element']['attributes']['href'])) {
            switch ($matches['type']) {
                case 'video':
                    return $this->createFigure(
                        $this->createEmbeddedVideoFromLink($link, $matches['url'])
                    );
                case 'script':
                    return $this->createFigure(
                        $this->createScriptFromLink($link, $matches['url'])
                    );
            }
        }

        return $link;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineUrl($Excerpt)
    {
        $link = parent::inlineUrl($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return null;
        }

        // External link
        return $this->handleExternalLink($link);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineUrlTag($Excerpt)
    {
        $link = parent::inlineUrlTag($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return null;
        }

        // External link
        return $this->handleExternalLink($link);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineCode($Excerpt)
    {
        $code = parent::inlineCode($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($code)) {
            return null;
        }

        // disable translation of code
        if (!isset($code['element']['attributes'])) {
            $code['element']['attributes'] = [];
        }
        $code['element']['attributes']['translate'] = 'no';

        return $code;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function inlineImage($Excerpt)
    {
        $InlineImage = parent::inlineImage($Excerpt); // @phpstan-ignore staticMethod.notFound
        if (!isset($InlineImage)) {
            return null;
        }

        // remove link attributes
        unset($InlineImage['element']['attributes']['target'], $InlineImage['element']['attributes']['rel']);

        // normalize path
        $InlineImage['element']['attributes']['src'] = $this->normalizePath((string) $InlineImage['element']['attributes']['src']);

        // should be lazy loaded?
        if ($this->config->isEnabled('pages.body.images.lazy') && !isset($InlineImage['element']['attributes']['loading'])) {
            $InlineImage['element']['attributes']['loading'] = 'lazy';
        }
        // should be decoding async?
        if ($this->config->isEnabled('pages.body.images.decoding') && !isset($InlineImage['element']['attributes']['decoding'])) {
            $InlineImage['element']['attributes']['decoding'] = 'async';
        }
        // add default class?
        if ((string) $this->config->get('pages.body.images.class')) {
            if (!\array_key_exists('class', $InlineImage['element']['attributes'])) {
                $InlineImage['element']['attributes']['class'] = '';
            }
            $InlineImage['element']['attributes']['class'] .= ' ' . (string) $this->config->get('pages.body.images.class');
            $InlineImage['element']['attributes']['class'] = trim($InlineImage['element']['attributes']['class']);
        }

        // not processed as an asset?
        if (!$this->assets) {
            $InlineImage['element']['attributes']['src'] = $this->getFileUrl((string) $InlineImage['element']['attributes']['src']);

            return $this->createFigure($InlineImage);
        }

        // disable remote image handling?
        if (Util\File::isRemote($InlineImage['element']['attributes']['src']) && !$this->config->isEnabled('pages.body.images.remote')) {
            return $this->createFigure($InlineImage);
        }

        // create asset
        $assetOptions = [];
        if ($this->config->isEnabled('pages.body.images.remote.fallback')) {
            $assetOptions += ['fallback' => (string) $this->config->get('pages.body.images.remote.fallback')];
        }
        $assetOptions += ['language' => $this->language];
        $asset = $this->getCachedAsset((string) $InlineImage['element']['attributes']['src'], $assetOptions);
        $InlineImage['element']['attributes']['src'] = new Url($this->builder, $asset);
        $width = $asset['width'];

        /*
         * Should be resized?
         */
        $shouldResize = false;
        $assetResized = null;
        // pages.body.images.resize
        if (
            \is_int($this->config->get('pages.body.images.resize'))
            && $this->config->get('pages.body.images.resize') > 0
            && $width > $this->config->get('pages.body.images.resize')
        ) {
            $shouldResize = true;
            $width = $this->config->get('pages.body.images.resize');
        }
        // width attribute
        if (
            isset($InlineImage['element']['attributes']['width'])
            && $width > (int) $InlineImage['element']['attributes']['width']
        ) {
            $shouldResize = true;
            $width = (int) $InlineImage['element']['attributes']['width'];
        }
        // responsive images
        if (
            $this->config->isEnabled('pages.body.images.responsive')
            && !empty($this->config->getAssetsImagesWidths())
            && $width > max($this->config->getAssetsImagesWidths())
        ) {
            $shouldResize = true;
            $width = max($this->config->getAssetsImagesWidths());
        }
        if ($shouldResize) {
            try {
                $assetResized = $asset->resize($width);
                $InlineImage['element']['attributes']['src'] = new Url($this->builder, $assetResized);
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $this->createFigure($InlineImage);
            }
        }

        // set width
        $InlineImage['element']['attributes']['width'] = $width;
        // set height
        $InlineImage['element']['attributes']['height'] = $assetResized['height'] ?? $asset['height'];

        // placeholder
        if (
            (!empty($this->config->get('pages.body.images.placeholder')) || isset($InlineImage['element']['attributes']['placeholder']))
            && \in_array($assetResized['subtype'] ?? $asset['subtype'], ['image/jpeg', 'image/png', 'image/gif'])
        ) {
            if (!\array_key_exists('placeholder', $InlineImage['element']['attributes'])) {
                $InlineImage['element']['attributes']['placeholder'] = (string) $this->config->get('pages.body.images.placeholder');
            }
            if (!\array_key_exists('style', $InlineImage['element']['attributes'])) {
                $InlineImage['element']['attributes']['style'] = '';
            }
            $InlineImage['element']['attributes']['style'] = trim($InlineImage['element']['attributes']['style'], ';');
            switch ($InlineImage['element']['attributes']['placeholder']) {
                case 'color':
                    $InlineImage['element']['attributes']['style'] .= \sprintf(';max-width:100%%;height:auto;background-color:%s;', $this->getCachedDominantColor($assetResized ?? $asset));
                    break;
                case 'lqip':
                    // aborts if animated GIF for performance reasons
                    if ($this->isAnimatedGifCached($assetResized ?? $asset)) {
                        break;
                    }
                    $InlineImage['element']['attributes']['style'] .= \sprintf(';max-width:100%%;height:auto;background-image:url(%s);background-repeat:no-repeat;background-position:center;background-size:cover;', $this->getCachedLqip($asset));
                    break;
            }
            unset($InlineImage['element']['attributes']['placeholder']);
            $InlineImage['element']['attributes']['style'] = trim($InlineImage['element']['attributes']['style']);
        }

        /*
         * Should be responsive?
         */
        $sizes = '';
        if ($this->config->isEnabled('pages.body.images.responsive')) {
            try {
                if (
                    $srcset = $this->getCachedSrcsetW($assetResized ?? $asset, $this->config->getAssetsImagesWidths())
                ) {
                    $InlineImage['element']['attributes']['srcset'] = $srcset;
                    $sizes = $this->getCachedSizes($InlineImage['element']['attributes']['class'] ?? '', (array) $this->config->getAssetsImagesSizes());
                    $InlineImage['element']['attributes']['sizes'] = $sizes;
                }
            } catch (\Exception $e) {
                $this->builder->getLogger()->warning($e->getMessage());
            }
        }

        /*
        <!-- if title: a <figure> is required to put in it a <figcaption> -->
        <figure>
            <!-- if formats: a <picture> is required for each <source> -->
            <picture>
                <source type="image/avif"
                    srcset="..."
                    sizes="..."
                >
                <source type="image/webp"
                    srcset="..."
                    sizes="..."
                >
                <img src="..."
                    srcset="..."
                    sizes="..."
                >
            </picture>
            <figcaption><!-- title --></figcaption>
        </figure>
        */

        $image = $InlineImage;

        // converts image to formats and put them in picture > source
        if (
            \count($formats = ((array) $this->config->get('pages.body.images.formats'))) > 0
            && \in_array($assetResized['subtype'] ?? $asset['subtype'], ['image/jpeg', 'image/png', 'image/gif'])
        ) {
            try {
                // abord if InlineImage is an animated GIF
                if ($this->isAnimatedGifCached($assetResized ?? $asset)) {
                    $filepath = Util::joinFile($this->config->getOutputPath(), $assetResized['path'] ?? $asset['path'] ?? '');
                    throw new RuntimeException(\sprintf('Asset "%s" is not converted (animated GIF).', $filepath));
                }
                $sources = [];
                foreach ($formats as $format) {
                    $srcset = '';
                    try {
                        $assetConverted = ($assetResized ?? $asset)->convert($format);
                        // build responsive images?
                        if ($this->config->isEnabled('pages.body.images.responsive')) {
                            $srcset = $this->getCachedSrcset($assetConverted, $this->config->getAssetsImagesWidths());
                        }
                        // if not, use default image as srcset
                        if (empty($srcset)) {
                            $srcset = (string) new Url($this->builder, $assetConverted);
                        }
                        // add format to <sources>
                        $sourceAttributes = [
                            'type'   => "image/$format",
                            'srcset' => $srcset,
                            'width'  => $InlineImage['element']['attributes']['width'],
                            'height' => $InlineImage['element']['attributes']['height'],
                        ];
                        if (!empty($sizes)) {
                            $sourceAttributes['sizes'] = $sizes;
                        }
                        $sources[] = [
                            'name'       => 'source',
                            'attributes' => $sourceAttributes,
                        ];
                    } catch (\Exception $e) {
                        $this->builder->getLogger()->warning($e->getMessage());
                        continue;
                    }
                }
                if (\count($sources) > 0) {
                    $picture = [
                        'extent'  => $InlineImage['extent'],
                        'element' => [
                            'name'       => 'picture',
                            'handler'    => 'elements',
                            'attributes' => [
                                'title' => $image['element']['attributes']['title'],
                            ],
                        ],
                    ];
                    $picture['element']['text'] = $sources;
                    unset($image['element']['attributes']['title']); // phpstan-ignore unset.offset
                    $picture['element']['text'][] = $image['element'];
                    $image = $picture;
                }
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
            }
        }

        // dark color-scheme variant: auto-detect `{filename}{suffix}.{ext}` alongside the source image
        $variantSources = [];
        $darkSuffix = (string) $this->config->get('pages.body.images.dark_suffix');
        if (!empty($darkSuffix)) {
            $darkSourceAttributes = $this->getCachedDarkSourceAttributes(
                $asset,
                $darkSuffix,
                $formats,
                [
                    'responsive' => $this->config->isEnabled('pages.body.images.responsive'),
                    'widths' => $this->config->getAssetsImagesWidths(),
                    'densities' => $this->config->getAssetsImagesDensities(),
                    'sizes' => !empty($sizes) ? $sizes : null,
                    'width1x' => isset($InlineImage['element']['attributes']['width']) && $InlineImage['element']['attributes']['width'] > 0
                        ? (int) $InlineImage['element']['attributes']['width']
                        : null,
                    'assetOptions' => ['language' => $this->language],
                ]
            );
            if (\count($darkSourceAttributes) > 0) {
                $variantSources = array_merge(
                    $variantSources,
                    array_map(static fn (array $attributes): array => ['name' => 'source', 'attributes' => $attributes], $darkSourceAttributes)
                );
            }
        }

        // mobile variant: auto-detect `{filename}{suffix}.{ext}` alongside the source image
        $mobileSuffix = (string) $this->config->get('pages.body.images.mobile_suffix');
        $mobileMediaQuery = (string) $this->config->get('pages.body.images.mobile_media_query');
        if (!empty($mobileSuffix)) {
            $mobileSourceAttributes = $this->getCachedMobileSourceAttributes(
                $asset,
                $mobileSuffix,
                $formats,
                [
                    'responsive' => $this->config->isEnabled('pages.body.images.responsive'),
                    'widths' => $this->config->getAssetsImagesWidths(),
                    'densities' => $this->config->getAssetsImagesDensities(),
                    'sizes' => !empty($sizes) ? $sizes : null,
                    'width1x' => isset($InlineImage['element']['attributes']['width']) && $InlineImage['element']['attributes']['width'] > 0
                        ? (int) $InlineImage['element']['attributes']['width']
                        : null,
                    'assetOptions' => ['language' => $this->language],
                    'media' => $mobileMediaQuery,
                    'darkSuffix' => $darkSuffix,
                ]
            );
            if (\count($mobileSourceAttributes) > 0) {
                // the first matching `<source>` wins: mobile sources must precede dark sources
                $variantSources = array_merge(
                    array_map(static fn (array $attributes): array => ['name' => 'source', 'attributes' => $attributes], $mobileSourceAttributes),
                    $variantSources
                );
            }
        }

        if (\count($variantSources) > 0) {
            // prepend variant sources to existing <picture>, or wrap <img> in a new <picture>
            if ($image['element']['name'] === 'picture') {
                array_splice($image['element']['text'], 0, 0, $variantSources);
            } else {
                $imageTitle = $image['element']['attributes']['title'] ?? null;
                unset($image['element']['attributes']['title']);
                $picture = [
                    'extent'  => $image['extent'],
                    'element' => [
                        'name'       => 'picture',
                        'handler'    => 'elements',
                        'attributes' => [],
                        'text'       => array_merge($variantSources, [$image['element']]),
                    ],
                ];
                if ($imageTitle !== null) {
                    $picture['element']['attributes']['title'] = $imageTitle;
                }
                $image = $picture;
            }
        }

        return $this->createFigure($image);
    }

    /**
     * Image block.
     *
     * @param array<string, mixed> $Excerpt
     *
     * @return array<string, mixed>|null
     */
    protected function blockImage($Excerpt)
    {
        if (1 !== preg_match($this->regexImage, $Excerpt['text'])) {
            return null;
        }

        $InlineImage = $this->inlineImage($Excerpt);
        if (!isset($InlineImage)) {
            return null;
        }

        return $InlineImage;
    }

    /**
     * Note block-level markup.
     *
     * :::tip
     * **Tip:** This is an advice.
     * :::
     *
     * A note can contain other notes: its fence must then be longer (e.g.: `::::tabs` … `::::`),
     * as a note is closed by a fence at least as long as its opening fence.
     *
     * A note is rendered with the `partials/notes/<type>.html.twig` template if it exists.
     *
     * Code inspired by https://github.com/sixlive/parsedown-alert from TJ Miller (@sixlive).
     *
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>|null
     */
    protected function blockNote($block)
    {
        if (preg_match('/(:{3,})(.*)/', $block['text'], $matches)) {
            $block = [
                'char'     => ':',
                'noteType' => trim($matches[2]),
                'fence'    => \strlen($matches[1]),
                'element' => [
                    'name'       => 'aside',
                    'text'       => '',
                    'attributes' => [
                        'class' => 'note',
                    ],
                ],
            ];
            if (!empty($matches[2])) {
                $block['element']['attributes']['class'] .= " note-{$matches[2]}";
            }

            return $block;
        }

        return null;
    }

    /**
     * Continues a note block.
     *
     * @param array<string, mixed> $line
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>|null
     */
    protected function blockNoteContinue($line, $block)
    {
        if (isset($block['complete'])) {
            return null;
        }
        // only a line made of `:::` closes the note (e.g.: not `:::` in an inline code),
        // at least as long as the opening fence (e.g.: not the `:::` of a note nested in a `::::` note)
        if (preg_match('/^:{' . $block['fence'] . ',}\s*$/', $line['text'])) {
            $block['complete'] = true;

            return $block;
        }
        // restores blank lines (not passed to continue methods)
        if (isset($block['interrupted'])) {
            $block['element']['text'] .= str_repeat("\n", $block['interrupted']);
            unset($block['interrupted']);
        }
        // keeps indentation (e.g.: in code blocks)
        $block['element']['text'] .= $line['body'] . "\n";

        return $block;
    }

    /**
     * Completes a note block.
     *
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>
     */
    protected function blockNoteComplete($block)
    {
        $type = (string) ($block['noteType'] ?? '');
        $text = (string) $block['element']['text'];
        unset($block['element']['text']);

        if (preg_match('/^[\w-]+$/', $type) && $this->hasNoteTemplate($type)) {
            $note = $this->noteVariables($type, $text);
            $html = $this->renderNote($note);
            if ($html !== null) {
                $block['element'] = [
                    'rawHtml'                => $html,
                    'allowRawHtmlInSafeMode' => true,
                    'autobreak'              => true,
                ];

                return $block;
            }
            $block['element']['rawHtml'] = $note['content'];

            return $block;
        }

        $block['element']['rawHtml'] = $this->noteText($text);

        return $block;
    }

    /**
     * Builds the variables of a note template.
     *
     * The note is split into items:
     * - one item per `####` heading (the content before the first heading is the intro)
     * - without heading, one item per entry of a list (a leading link is the item title and URL)
     * - without heading, one item per fenced code block (titled with its language)
     *
     * @return array{type: string, id: string, content: string, intro: string, items: list<array{title: string|null, heading: string|null, id: string|null, link: string|null, url: string|null, language: string|null, content: string}>}
     */
    protected function noteVariables(string $type, string $text): array
    {
        $note = [
            'type'    => $type,
            'id'      => \sprintf('note-%s-%d', $type, ++$this->notesCount),
            'content' => '',
            'intro'   => '',
            'items'   => [],
        ];
        $item = [
            'title'    => null,
            'heading'  => null,
            'id'       => null,
            'link'     => null,
            'url'      => null,
            'language' => null,
            'content'  => '',
        ];

        $segments = $this->splitNote($text);
        // headings
        if (\count($segments['items']) > 0) {
            $note['intro'] = $this->noteText(implode("\n", $segments['intro']));
            $content = [$note['intro']];
            foreach ($segments['items'] as $segment) {
                $heading = $this->noteText($segment['heading']);
                $body = $this->noteText(implode("\n", $segment['lines']));
                $title = preg_match('/^<h4[^>]*>(.*)<\/h4>$/s', $heading, $matches) ? $this->removeHeadingAnchor($matches[1]) : strip_tags($heading);
                $id = preg_match('/^<h4[^>]*\sid="([^"]*)"/', $heading, $matches) ? html_entity_decode($matches[1], \ENT_QUOTES | \ENT_HTML5) : null;
                $note['items'][] = array_merge($item, ['title' => $title, 'heading' => $heading, 'id' => $id, 'content' => $body]);
                $content[] = $heading;
                $content[] = $body;
            }
            $note['content'] = implode("\n", array_filter($content, fn ($html) => $html !== ''));

            return $note;
        }
        // list
        if (($entries = $this->splitNoteList($text)) !== null) {
            $note['content'] = $this->noteText($text);
            foreach ($entries as $entry) {
                $html = trim($this->line($entry));
                $title = $link = $url = null;
                // leading link, followed by a separator (`:` or `-`)
                if (preg_match('/^(<a\s[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>)(?:\s*[:\-–—](?=\s|$))?\s*(.*)$/s', $html, $matches)) {
                    $link = $matches[1];
                    $url = html_entity_decode($matches[2], \ENT_QUOTES | \ENT_HTML5);
                    $title = $matches[3];
                    $html = $matches[4];
                }
                $note['items'][] = array_merge($item, ['title' => $title, 'link' => $link, 'url' => $url, 'content' => $html]);
            }

            return $note;
        }
        // code blocks
        if (($blocks = $this->splitNoteCode($text)) !== null) {
            foreach ($blocks as $block) {
                $html = $this->noteText($block['code']);
                $note['items'][] = array_merge($item, ['title' => $block['language'] !== null ? htmlspecialchars($block['language']) : null, 'language' => $block['language'], 'content' => $html]);
            }
            $note['content'] = implode("\n", array_column($note['items'], 'content'));

            return $note;
        }
        // no items
        $note['content'] = $note['intro'] = $this->noteText($text);

        return $note;
    }

    /**
     * Splits the Markdown of a note on `####` headings (except in fenced code blocks and nested notes).
     *
     * @return array{intro: list<string>, items: list<array{heading: string, lines: list<string>}>}
     */
    protected function splitNote(string $text): array
    {
        $intro = [];
        $items = [];
        $heading = null;
        $lines = [];
        $fence = null;
        foreach (explode("\n", $text) as $line) {
            if ($fence !== null) {
                if (preg_match('/^ {0,3}' . preg_quote($fence[0], '/') . '{' . \strlen($fence) . ',}\s*$/', $line)) {
                    $fence = null;
                }
            } elseif (preg_match('/^ {0,3}(`{3,}|~{3,}|:{3,}(?=[ \t]*\S))/', $line, $matches)) {
                $fence = $matches[1];
            } elseif (preg_match('/^ {0,3}#{4}(?!#)/', $line)) {
                if ($heading !== null) {
                    $items[] = ['heading' => $heading, 'lines' => $lines];
                }
                $heading = $line;
                $lines = [];

                continue;
            }
            if ($heading === null) {
                $intro[] = $line;

                continue;
            }
            $lines[] = $line;
        }
        if ($heading !== null) {
            $items[] = ['heading' => $heading, 'lines' => $lines];
        }

        return ['intro' => $intro, 'items' => $items];
    }

    /**
     * Returns the entries (Markdown) of a note made of a single list, or null.
     *
     * @return list<string>|null
     */
    protected function splitNoteList(string $text): ?array
    {
        $entries = [];
        foreach (explode("\n", trim($text, "\n")) as $line) {
            if (trim($line) === '') {
                continue;
            }
            // new entry
            if (preg_match('/^(?:[*+-]|\d+[.)])[ \t]+(.*)$/', $line, $matches)) {
                $entries[] = trim($matches[1]);

                continue;
            }
            // continuation of the current entry
            if (\count($entries) > 0 && preg_match('/^[ \t]+(.*)$/', $line, $matches)) {
                $entries[\count($entries) - 1] .= ' ' . trim($matches[1]);

                continue;
            }

            return null;
        }

        return \count($entries) > 0 ? $entries : null;
    }

    /**
     * Returns the fenced code blocks of a note made only of code blocks, or null.
     *
     * @return list<array{code: string, language: string|null}>|null
     */
    protected function splitNoteCode(string $text): ?array
    {
        $blocks = [];
        $code = [];
        $language = null;
        $fence = null;
        foreach (explode("\n", $text) as $line) {
            if ($fence !== null) {
                $code[] = $line;
                if (preg_match('/^ {0,3}' . preg_quote($fence[0], '/') . '{' . \strlen($fence) . ',}\s*$/', $line)) {
                    $blocks[] = ['code' => implode("\n", $code), 'language' => $language];
                    $fence = null;
                }

                continue;
            }
            if (preg_match('/^ {0,3}(`{3,}|~{3,})\s*([^\s`{]*)/', $line, $matches)) {
                $fence = $matches[1];
                $code = [$line];
                $language = $matches[2] !== '' ? $matches[2] : null;

                continue;
            }
            if (trim($line) !== '') {
                return null;
            }
        }
        // unclosed code block
        if ($fence !== null) {
            $blocks[] = ['code' => implode("\n", $code), 'language' => $language];
        }

        return \count($blocks) > 0 ? $blocks : null;
    }

    /**
     * Converts the Markdown of a note, while the document is being parsed.
     *
     * Parsedown resets the definitions (footnotes, abbreviations and references) on each conversion:
     * those of the document are restored, otherwise they are lost for the rest of the document.
     */
    protected function noteText(string $text): string
    {
        $definitions = $this->DefinitionData; // @phpstan-ignore property.notFound
        try {
            return $this->text($text);
        } finally {
            $this->DefinitionData = $definitions; // @phpstan-ignore property.notFound
        }
    }

    /**
     * Checks if a note type has a template.
     */
    protected function hasNoteTemplate(string $type): bool
    {
        if (!isset(static::$notesTemplates[$type])) {
            static::$notesTemplates[$type] = $this->getNotesRenderer()->getTwig()->getLoader()->exists($this->getNoteTemplate($type));
        }

        return static::$notesTemplates[$type];
    }

    /**
     * Renders a note with its template, or returns null on error.
     *
     * @param array{type: string, id: string, content: string, intro: string, items: array<mixed>} $note
     */
    protected function renderNote(array $note): ?string
    {
        $renderer = $this->getNotesRenderer();
        // the translator uses the page language, the default locale is restored after rendering
        $locale = \extension_loaded('intl') ? \Locale::getDefault() : null;
        try {
            $renderer->setLocale($this->config->getLanguageProperty('locale', $this->language));

            return trim($renderer->render($this->getNoteTemplate($note['type']), ['note' => $note]));
        } catch (\Throwable $e) {
            $this->builder->getLogger()->error(\sprintf('Unable to render note "%s": %s', $note['type'], $e->getMessage()));

            return null;
        } finally {
            if ($locale !== null) {
                \Locale::setDefault($locale);
            }
        }
    }

    /**
     * Returns the template name of a note type.
     */
    protected function getNoteTemplate(string $type): string
    {
        return \sprintf('partials/notes/%s.html.twig', $type);
    }

    /**
     * Returns the notes templates renderer (created once per build).
     */
    protected function getNotesRenderer(): Twig
    {
        if (static::$notesRenderer === null) {
            static::$notesRenderer = new Twig($this->builder, $this->config->getLayoutsPaths());
        }

        return static::$notesRenderer;
    }

    /**
     * Apply Highlight to code blocks.
     *
     * @param array<string, mixed> $block
     *
     * @return array<string, mixed>
     */
    protected function blockFencedCodeComplete($block)
    {
        // disable translation of code
        if (!isset($block['element']['element']['attributes'])) {
            $block['element']['element']['attributes'] = [];
        }
        $block['element']['element']['attributes']['translate'] = 'no';
        // language of the code, available to style it (e.g.: as a label)
        $class = (string) ($block['element']['element']['attributes']['class'] ?? '');
        if (str_starts_with($class, 'language-')) {
            $block['element']['attributes']['data-lang'] = substr($class, \strlen('language-'));
        }

        // no highlighting without language
        if (!$this->config->isEnabled('pages.body.highlight') || $class === '') {
            return $block;
        }

        try {
            $code = $block['element']['element']['text'];
            $languageClass = $block['element']['element']['attributes']['class'];
            $language = explode('-', $languageClass);
            $this->highlighter ??= HighlighterFactory::create($this->config);
            $highlighted = $this->highlighter->highlight($code, $language[1]);
            $block['element']['element']['attributes']['class'] = trim(\sprintf('%s %s', $languageClass, $highlighted->class));
            $block['element']['element']['rawHtml'] = $highlighted->html;
            $block['element']['element']['allowRawHtmlInSafeMode'] = true;
            unset($block['element']['element']['text']);
        } catch (\Exception $e) {
            $this->builder->getLogger()->debug("Highlighter: " . $e->getMessage());
        } finally {
            return $block;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param string $attributeString
     *
     * @return array<string, string>
     */
    protected function parseAttributeData($attributeString)
    {
        $attributes = preg_split('/[ ]+/', $attributeString, -1, PREG_SPLIT_NO_EMPTY);
        $Data = [];
        $HtmlAtt = [];

        if (is_iterable($attributes)) {
            foreach ($attributes as $attribute) {
                switch ($attribute[0]) {
                    case '#': // ID
                        $Data['id'] = substr($attribute, 1);
                        break;
                    case '.': // Classes
                        $classes[] = substr($attribute, 1);
                        break;
                    default:  // Attributes
                        parse_str($attribute, $parsed);
                        $HtmlAtt = array_merge($HtmlAtt, $parsed);
                }
            }

            if (isset($classes)) {
                $Data['class'] = implode(' ', $classes);
            }
            if (!empty($HtmlAtt)) {
                foreach ($HtmlAtt as $a => $v) {
                    // ignores array values (e.g.: `a[]=b`)
                    if (!\is_string($v)) {
                        continue;
                    }
                    $Data[(string) $a] = trim($v, '"');
                }
            }
        }

        return $Data;
    }

    /**
     * {@inheritdoc}
     *
     * Completes the parent transliteration table, which handles "æ" but not the
     * French ligature "œ": without this, "cœur" would keep a non-ASCII anchor
     * while "größe" becomes "grosse".
     *
     * @return string
     */
    protected function transliterate(string $text): string
    {
        return parent::transliterate(strtr($text, ['Œ' => 'OE', 'œ' => 'oe']));
    }

    /**
     * {@inheritdoc}
     *
     * Converts XHTML '<br />' tag to '<br>'.
     *
     * @param string $text
     *
     * @return string
     */
    protected function unmarkedText($text)
    {
        return str_replace('<br />', '<br>', parent::unmarkedText($text)); // @phpstan-ignore staticMethod.notFound
    }

    /**
     * {@inheritdoc}
     *
     * XHTML closing tag to HTML5 closing tag, and adds an anchor link to headings with an ID.
     *
     * @param array<string, mixed> $Element
     *
     * @return string
     */
    protected function element(array $Element)
    {
        $html = str_replace(' />', '>', parent::element($Element)); // @phpstan-ignore staticMethod.notFound

        $name = $Element['name'] ?? null;
        $id = $Element['attributes']['id'] ?? null;
        if (\is_string($name) && \in_array($name, $this->anchors, true) && \is_string($id) && $id !== '' && str_ends_with($html, "</$name>")) {
            $anchor = \sprintf('<a class="heading-anchor" href="#%s" aria-hidden="true" tabindex="-1"></a>', htmlspecialchars($id, \ENT_QUOTES, 'UTF-8'));
            $html = substr($html, 0, -\strlen("</$name>")) . $anchor . "</$name>";
        }

        return $html;
    }

    /**
     * Removes the anchor link of a heading HTML.
     */
    protected function removeHeadingAnchor(string $html): string
    {
        return preg_replace('/<a class="heading-anchor"[^>]*><\/a>/', '', $html) ?? $html;
    }

    /**
     * Turns a path relative to static or assets into a website relative path.
     *
     *   "../../assets/images/img.jpeg"
     *   ->
     *   "/images/img.jpeg"
     */
    private function normalizePath(string $path): string
    {
        // https://regex101.com/r/Rzguzh/1
        $pattern = \sprintf(
            '(\.\.\/)+(\b%s|%s\b)+(\/.*)',
            (string) $this->config->get('static.dir'),
            (string) $this->config->get('assets.dir')
        );
        $path = Util::joinPath($path);
        if (!preg_match('/' . $pattern . '/is', $path, $matches)) {
            return $path;
        }

        return $matches[3];
    }

    /**
     * @param array<string, mixed> $assetOptions
     */
    private function getCachedAsset(string $path, array $assetOptions): Asset
    {
        return $this->rememberAsset(
            [
                'path' => $path,
                'options' => $assetOptions,
            ],
            fn () => new Asset($this->builder, $path, $assetOptions)
        );
    }

    private function getCachedDominantColor(Asset $asset): string
    {
        return (string) $this->rememberImageProcessing(
            'dominant',
            ['asset' => $this->getAssetIdentity($asset)],
            static fn () => Image::getDominantColor($asset)
        );
    }

    private function getCachedLqip(Asset $asset): string
    {
        return (string) $this->rememberImageProcessing(
            'lqip',
            ['asset' => $this->getAssetIdentity($asset)],
            static fn () => Image::getLqip($asset)
        );
    }

    private function isAnimatedGifCached(Asset $asset): bool
    {
        return (bool) $this->rememberImageProcessing(
            'animated',
            ['asset' => $this->getAssetIdentity($asset)],
            static fn () => Image::isAnimatedGif($asset)
        );
    }

    /**
     * @param array<int> $widths
     */
    private function getCachedSrcsetW(Asset $asset, array $widths): string
    {
        return (string) $this->rememberImageProcessing(
            'srcsetw',
            ['asset' => $this->getAssetIdentity($asset), 'widths' => $widths],
            fn () => Image::buildHtmlSrcsetW($asset, $widths, false, $this->getUrlBuilder())
        );
    }

    /**
     * @param array<int> $widths
     */
    private function getCachedSrcset(Asset $asset, array $widths): string
    {
        return (string) $this->rememberImageProcessing(
            'srcset',
            ['asset' => $this->getAssetIdentity($asset), 'widths' => $widths],
            fn () => Image::buildHtmlSrcset($asset, $widths, false, $this->getUrlBuilder())
        );
    }

    /**
     * @param array<string, string> $sizesConfig
     */
    private function getCachedSizes(string $class, array $sizesConfig): string
    {
        return (string) $this->rememberImageProcessing(
            'sizes',
            ['class' => $class, 'sizes' => $sizesConfig],
            static fn () => Image::getHtmlSizes($class, $sizesConfig)
        );
    }

    /**
     * @param array<string> $formats
     * @param array{
     *   responsive?: mixed,
     *   widths?: array<int>,
     *   densities?: array<float|int>,
     *   sizes?: ?string,
     *   width1x?: ?int,
     *   assetOptions?: array<mixed>
     * } $options
     *
     * @return array<array<string, string>>
     */
    private function getCachedDarkSourceAttributes(Asset $asset, string $darkSuffix, array $formats, array $options): array
    {
        return (array) $this->rememberImageProcessing(
            'dark',
            [
                'asset' => $this->getAssetIdentity($asset),
                'suffix' => $darkSuffix,
                'formats' => $formats,
                'options' => $options,
            ],
            fn () => Image::buildDarkSourceAttributes(
                $this->builder,
                $asset,
                $darkSuffix,
                $formats,
                $options + ['url' => $this->getUrlBuilder()]
            )
        );
    }

    /**
     * @param array<string> $formats
     * @param array{
     *   responsive?: mixed,
     *   widths?: array<int>,
     *   densities?: array<float|int>,
     *   sizes?: ?string,
     *   width1x?: ?int,
     *   assetOptions?: array<mixed>,
     *   media?: string,
     *   darkSuffix?: ?string
     * } $options
     *
     * @return array<array<string, string>>
     */
    private function getCachedMobileSourceAttributes(Asset $asset, string $mobileSuffix, array $formats, array $options): array
    {
        return (array) $this->rememberImageProcessing(
            'mobile',
            [
                'asset' => $this->getAssetIdentity($asset),
                'suffix' => $mobileSuffix,
                'formats' => $formats,
                'options' => $options,
            ],
            fn () => Image::buildMobileSourceAttributes(
                $this->builder,
                $asset,
                $mobileSuffix,
                $formats,
                $options + ['url' => $this->getUrlBuilder()]
            )
        );
    }

    /**
     * Returns a callable that builds the URL of an Asset.
     */
    private function getUrlBuilder(): callable
    {
        return fn (Asset $asset): string => (string) new Url($this->builder, $asset);
    }

    private function getAssetIdentity(Asset $asset): string
    {
        return \sprintf(
            '%s|%s|%s|%sx%s',
            (string) $asset['path'],
            (string) $asset['hash'],
            (string) $asset['subtype'],
            (string) ($asset['width'] ?? ''),
            (string) ($asset['height'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getCacheKey(string $prefix, array $payload): string
    {
        return \sprintf('%s_%s', $prefix, hash('xxh128', serialize($payload)));
    }

    /**
     * Memoize an Asset instance across Parsedown instances.
     *
     * @param array<string, mixed> $payload
     */
    private function rememberAsset(array $payload, callable $factory): Asset
    {
        $cacheKey = $this->getCacheKey('asset', $payload);

        return $this->builder->rememberAsset($cacheKey, $factory);
    }

    /**
     * Memoize an image-processing result across Parsedown instances.
     *
     * @param array<string, mixed> $payload
     */
    private function rememberImageProcessing(string $prefix, array $payload, callable $factory): mixed
    {
        $cacheKey = $this->getCacheKey($prefix, $payload);
        if (!isset(static::$imageProcessingCache[$cacheKey])) {
            static::$imageProcessingCache[$cacheKey] = $factory();
        }

        return static::$imageProcessingCache[$cacheKey];
    }

    /**
     * Create a media (video or audio) element from a link.
     *
     * @param array<string, mixed> $link
     *
     * @return array<string, mixed>
     */
    private function createMediaFromLink(array $link, string $type = 'video'): array
    {
        $block = [
            'extent'  => $link['extent'],
            'element' => [
                // the text of the link is the fallback content (and closes the element, even if empty)
                'handler'      => $link['element']['handler'],
                'nonNestables' => $link['element']['nonNestables'] ?? [],
            ],
        ];
        $block['element']['attributes'] = $link['element']['attributes'];
        unset($block['element']['attributes']['href']);
        $block['element']['attributes']['src'] = $this->getMediaUrl((string) $link['element']['attributes']['href']);
        switch ($type) {
            case 'video':
                $block['element']['name'] = 'video';
                // no controls = autoplay, loop, muted, playsinline
                if (!isset($block['element']['attributes']['controls'])) {
                    $block['element']['attributes']['autoplay'] = '';
                    $block['element']['attributes']['loop'] = '';
                    $block['element']['attributes']['muted'] = '';
                    $block['element']['attributes']['playsinline'] = '';
                }
                if (isset($block['element']['attributes']['poster'])) {
                    $block['element']['attributes']['poster'] = $this->getMediaUrl((string) $block['element']['attributes']['poster']);
                }
                if (!\array_key_exists('style', $block['element']['attributes'])) {
                    $block['element']['attributes']['style'] = '';
                }
                $block['element']['attributes']['style'] .= ';max-width:100%;height:auto;background-color:#d8d8d8;'; // background color if offline

                return $block;
            case 'audio':
                $block['element']['name'] = 'audio';

                return $block;
        }

        throw new \Exception(\sprintf('Unable to create %s from "%s".', $type, $link['element']['attributes']['href']));
    }

    /**
     * Returns the URL of a not embedded local media (video or audio) link.
     *
     * The local file is handled as an asset, so its URL matches the published (and fingerprinted) file.
     * Remote and missing files are linked as is.
     */
    private function getMediaLinkUrl(string $href): Url|string
    {
        if (!$this->assets) {
            return $this->getFileUrl($href);
        }
        if (!Util\File::isRemote($href)) {
            $asset = $this->getCachedAsset($href, ['ignore_missing' => true]);
            if (!$asset->isMissing()) {
                return new Url($this->builder, $asset);
            }
        }

        return new Url($this->builder, $href);
    }

    /**
     * Returns the URL of an embedded media file (video, audio or poster).
     */
    private function getMediaUrl(string $path): Url|string
    {
        if (!$this->assets) {
            return $this->getFileUrl($path);
        }

        return new Url($this->builder, $this->getCachedAsset($path, []));
    }

    /**
     * Returns the URL of a file of the body not processed as an asset:
     * given by the `asset_url` option for a local file, as is otherwise.
     */
    private function getFileUrl(string $path): string
    {
        if ($this->assetUrl === null || $path === '' || Util\File::isRemote($path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        return (string) ($this->assetUrl)($path);
    }

    /**
     * Create an embedded video iframe element from a link element and an URL.
     *
     * @param array<string, mixed> $link
     *
     * @return array<string, mixed>
     */
    private function createEmbeddedVideoFromLink(array $link, string $url): array
    {
        $iframe = [
            'element' => [
                'name'       => 'iframe',
                'text'       => '',
                'attributes' => [
                    'src'             => $url,
                    'loading'         => 'lazy',
                    'width'           => '640',
                    'height'          => '360',
                    'title'           => $this->getLinkText($link),
                    'frameborder'     => '0',
                    'allow'           => 'accelerometer;autoplay;encrypted-media;gyroscope;picture-in-picture;fullscreen;web-share;',
                    'allowfullscreen' => '',
                    'style'           => 'position:absolute;top:0;left:0;width:100%;height:100%;border:0;background-color:#d8d8d8;',
                ],
            ],
        ];

        // div wrapper
        return [
            'extent'  => $link['extent'],
            'element' => [
                'name'    => 'div',
                'handler' => 'elements',
                'text'    => [
                    $iframe['element'],
                ],
                'attributes' => [
                    'title' => $link['element']['attributes']['title'],
                    'style' => 'position:relative;padding-bottom:56.25%;height:0;overflow:hidden;',
                ],
            ],
        ];
    }

    /**
     * Returns the plain text of a link element (`null` if empty).
     *
     * Parsedown 1.8 keeps the text of a link in its handler argument, not in a `text` key.
     *
     * @param array<string, mixed> $link
     */
    private function getLinkText(array $link): ?string
    {
        $text = trim(strip_tags($this->line((string) ($link['element']['handler']['argument'] ?? ''))));

        return $text === '' ? null : html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5);
    }

    /**
     * Create a script element from a link element and an URL.
     *
     * @param array<string, mixed> $link
     *
     * @return array<string, mixed>
     */
    private function createScriptFromLink(array $link, string $url): array
    {
        return [
            'extent'  => $link['extent'],
            'element' => [
                'name'       => 'script',
                'text'       => '', // an empty text (not null) is required to render the closing tag
                'attributes' => [
                    'src'   => $url . '.js',
                    'title' => $link['element']['attributes']['title'],
                ],
            ],
        ];
    }

    /**
     * Create a figure > figcaption element.
     *
     * @param array<string, mixed> $inline
     *
     * @return array<string, mixed>
     */
    private function createFigure(array $inline): array
    {
        if (!$this->config->isEnabled('pages.body.images.caption')) {
            return $inline;
        }

        if (empty($inline['element']['attributes']['title'])) {
            return $inline;
        }

        $titleRawHtml = $this->line($inline['element']['attributes']['title']);
        $inline['element']['attributes']['title'] = strip_tags($titleRawHtml);

        $figcaption = [
            'element' => [
                'name'                   => 'figcaption',
                'allowRawHtmlInSafeMode' => true,
                'rawHtml'                => $titleRawHtml,
            ],
        ];
        $figure = [
            'extent'  => $inline['extent'],
            'element' => [
                'name'    => 'figure',
                'handler' => 'elements',
                'text'    => [
                    $inline['element'],
                    $figcaption['element'],
                ],
            ],
        ];

        return $figure;
    }

    /**
     * Handle an external link.
     *
     * @param array<string, mixed> $link
     *
     * @return array<string, mixed>
     */
    private function handleExternalLink(array $link): array
    {
        if (
            str_starts_with($link['element']['attributes']['href'], 'http')
            && (!empty($this->config->get('baseurl')) && !str_starts_with($link['element']['attributes']['href'], (string) $this->config->get('baseurl')))
        ) {
            if ($this->config->isEnabled('pages.body.links.external.blank')) {
                $link['element']['attributes']['target'] = '_blank';
            }
            if (!\array_key_exists('rel', $link['element']['attributes'])) {
                $link['element']['attributes']['rel'] = '';
            }
            if ($this->config->isEnabled('pages.body.links.external.noopener')) {
                $link['element']['attributes']['rel'] .= ' noopener';
            }
            if ($this->config->isEnabled('pages.body.links.external.noreferrer')) {
                $link['element']['attributes']['rel'] .= ' noreferrer';
            }
            if ($this->config->isEnabled('pages.body.links.external.nofollow')) {
                $link['element']['attributes']['rel'] .= ' nofollow';
            }
            $link['element']['attributes']['rel'] = trim($link['element']['attributes']['rel']);
        }

        return $link;
    }
}
