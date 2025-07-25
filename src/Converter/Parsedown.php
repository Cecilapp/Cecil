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

use Cecil\Assets\Asset;
use Cecil\Assets\Image;
use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Cecil\Url;
use Cecil\Util;
use Highlight\Highlighter;

/**
 * Parsedown class.
 *
 * This class extends ParsedownExtra (and ParsedownToc) and provides methods to parse Markdown content
 * with additional features such as inline insertions, image handling, note blocks,
 * and code highlighting.
 *
 * @property array $InlineTypes
 * @property string $inlineMarkerList
 * @property array $specialCharacters
 * @property array $BlockTypes
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

    /** @var Highlighter */
    protected $highlighter;

    public function __construct(Builder $builder, ?array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();

        // "insert" line block: ++text++ -> <ins>text</ins>
        $this->InlineTypes['+'][] = 'Insert';
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));
        $this->specialCharacters[] = '+';

        // Image block (to avoid paragraph)
        $this->BlockTypes['!'][] = 'Image';

        // "notes" block
        $this->BlockTypes[':'][] = 'Note';

        // code highlight
        $this->highlighter = new Highlighter();

        // options
        $options = array_merge(['selectors' => (array) $this->config->get('pages.body.toc')], $options ?? []);

        parent::__construct();
        parent::setOptions($options);
    }

    /**
     * Insert inline.
     * e.g.: ++text++ -> <ins>text</ins>.
     */
    protected function inlineInsert($Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
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
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineLink($Excerpt)
    {
        $link = parent::inlineLink($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return null;
        }

        // Link to a page with "page:page_id" as URL
        if (Util\Str::startsWith($link['element']['attributes']['href'], 'page:')) {
            $link['element']['attributes']['href'] = new Url($this->builder, substr($link['element']['attributes']['href'], 5, \strlen($link['element']['attributes']['href'])));

            return $link;
        }

        // External link
        $link = $this->handleExternalLink($link);

        /*
         * Embed link?
         */
        $embed = $this->config->isEnabled('pages.body.links.embed');
        if (isset($link['element']['attributes']['embed'])) {
            $embed = true;
            if ($link['element']['attributes']['embed'] == 'false') {
                $embed = false;
            }
            unset($link['element']['attributes']['embed']);
        }
        $extension = pathinfo($link['element']['attributes']['href'], PATHINFO_EXTENSION);
        // video?
        if (\in_array($extension, $this->config->get('pages.body.links.embed.video') ?? [])) {
            if (!$embed) {
                $link['element']['attributes']['href'] = (string) new Asset($this->builder, $link['element']['attributes']['href'], ['leading_slash' => false]);

                return $link;
            }
            $video = $this->createMediaFromLink($link, 'video');
            if ($this->config->isEnabled('pages.body.images.caption')) {
                return $this->createFigure($video);
            }

            return $video;
        }
        // audio?
        if (\in_array($extension, $this->config->get('pages.body.links.embed.audio') ?? [])) {
            if (!$embed) {
                $link['element']['attributes']['href'] = (string) new Asset($this->builder, $link['element']['attributes']['href'], ['leading_slash' => false]);

                return $link;
            }
            $audio = $this->createMediaFromLink($link, 'audio');
            if ($this->config->isEnabled('pages.body.images.caption')) {
                return $this->createFigure($audio);
            }

            return $audio;
        }
        if (!$embed) {
            return $link;
        }
        // GitHub Gist link?
        // https://regex101.com/r/QmCiAL/1
        $pattern = 'https:\/\/gist\.github.com\/[-a-zA-Z0-9_]+\/[-a-zA-Z0-9_]+';
        if (preg_match('/' . $pattern . '/is', (string) $link['element']['attributes']['href'], $matches)) {
            $gist = [
                'extent'  => $link['extent'],
                'element' => [
                    'name'       => 'script',
                    'text'       => $link['element']['text'],
                    'attributes' => [
                        'src'   => $matches[0] . '.js',
                        'title' => $link['element']['attributes']['title'],
                    ],
                ],
            ];
            if ($this->config->isEnabled('pages.body.images.caption')) {
                return $this->createFigure($gist);
            }

            return $gist;
        }
        // Youtube link?
        // https://regex101.com/r/gznM1j/1
        $pattern = '(?:https?:\/\/)?(?:www\.)?youtu(?:\.be\/|be.com\/\S*(?:watch|embed)(?:(?:(?=\/[-a-zA-Z0-9_]{11,}(?!\S))\/)|(?:\S*v=|v\/)))([-a-zA-Z0-9_]{11,})';
        if (preg_match('/' . $pattern . '/is', (string) $link['element']['attributes']['href'], $matches)) {
            $iframe = [
                'element' => [
                    'name'       => 'iframe',
                    'text'       => $link['element']['text'],
                    'attributes' => [
                        'width'           => '560',
                        'height'          => '315',
                        'title'           => $link['element']['text'],
                        'src'             => 'https://www.youtube.com/embed/' . $matches[1],
                        'frameborder'     => '0',
                        'allow'           => 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture',
                        'allowfullscreen' => '',
                        'style'           => 'position:absolute; top:0; left:0; width:100%; height:100%; border:0',
                    ],
                ],
            ];
            $youtube = [
                'extent'  => $link['extent'],
                'element' => [
                    'name'    => 'div',
                    'handler' => 'elements',
                    'text'    => [
                        $iframe['element'],
                    ],
                    'attributes' => [
                        'style' => 'position:relative; padding-bottom:56.25%; height:0; overflow:hidden',
                        'title' => $link['element']['attributes']['title'],
                    ],
                ],
            ];
            if ($this->config->isEnabled('pages.body.images.caption')) {
                return $this->createFigure($youtube);
            }

            return $youtube;
        }

        return $link;
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineUrl($Excerpt)
    {
        $link = parent::inlineUrl($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return;
        }

        // External link
        return $this->handleExternalLink($link);
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineUrlTag($Excerpt)
    {
        $link = parent::inlineUrlTag($Excerpt); // @phpstan-ignore staticMethod.notFound

        if (!isset($link)) {
            return;
        }

        // External link
        return $this->handleExternalLink($link);
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineImage($Excerpt)
    {
        $InlineImage = parent::inlineImage($Excerpt); // @phpstan-ignore staticMethod.notFound
        if (!isset($InlineImage)) {
            return null;
        }

        // normalize path
        $InlineImage['element']['attributes']['src'] = $this->normalizePath($InlineImage['element']['attributes']['src']);

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

        // disable remote image handling?
        if (Util\File::isRemote($InlineImage['element']['attributes']['src']) && !$this->config->isEnabled('pages.body.images.remote')) {
            return $InlineImage;
        }

        // create asset
        $assetOptions = ['leading_slash' => false];
        if ($this->config->isEnabled('pages.body.images.remote.fallback')) {
            $assetOptions = ['leading_slash' => true];
            $assetOptions += ['fallback' => (string) $this->config->get('pages.body.images.remote.fallback')];
        }
        $asset = new Asset($this->builder, $InlineImage['element']['attributes']['src'], $assetOptions);
        $InlineImage['element']['attributes']['src'] = $asset;
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
                $InlineImage['element']['attributes']['src'] = $assetResized;
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $InlineImage;
            }
        }

        // set width
        $InlineImage['element']['attributes']['width'] = $width;
        // set height
        $InlineImage['element']['attributes']['height'] = $assetResized['height'] ?? $asset['height'];

        // placeholder
        if (
            (!empty($this->config->get('pages.body.images.placeholder')) || isset($InlineImage['element']['attributes']['placeholder']))
            && \in_array($InlineImage['element']['attributes']['src']['subtype'], ['image/jpeg', 'image/png', 'image/gif'])
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
                    $InlineImage['element']['attributes']['style'] .= \sprintf(';max-width:100%%;height:auto;background-color:%s;', Image::getDominantColor($InlineImage['element']['attributes']['src']));
                    break;
                case 'lqip':
                    // aborts if animated GIF for performance reasons
                    if (Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
                        break;
                    }
                    $InlineImage['element']['attributes']['style'] .= \sprintf(';max-width:100%%;height:auto;background-image:url(%s);background-repeat:no-repeat;background-position:center;background-size:cover;', Image::getLqip($InlineImage['element']['attributes']['src']));
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
                    $srcset = Image::buildSrcset(
                        $assetResized ?? $asset,
                        $this->config->getAssetsImagesWidths()
                    )
                ) {
                    $InlineImage['element']['attributes']['srcset'] = $srcset;
                    $sizes = Image::getSizes($InlineImage['element']['attributes']['class'] ?? '', (array) $this->config->getAssetsImagesSizes());
                    $InlineImage['element']['attributes']['sizes'] = $sizes;
                }
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
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
            && \in_array($InlineImage['element']['attributes']['src']['subtype'], ['image/jpeg', 'image/png', 'image/gif'])
        ) {
            try {
                // InlineImage src must be an Asset instance
                if (!$InlineImage['element']['attributes']['src'] instanceof Asset) {
                    throw new RuntimeException(\sprintf('Asset "%s" can\'t be converted.', $InlineImage['element']['attributes']['src']));
                }
                // abord if InlineImage is an animated GIF
                if (Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
                    $filepath = Util::joinFile($this->config->getOutputPath(), $InlineImage['element']['attributes']['src']['path']);
                    throw new RuntimeException(\sprintf('Asset "%s" is not converted (animated GIF).', $filepath));
                }
                $sources = [];
                foreach ($formats as $format) {
                    $srcset = '';
                    try {
                        $assetConverted = $InlineImage['element']['attributes']['src']->convert($format);
                    } catch (\Exception $e) {
                        $this->builder->getLogger()->debug($e->getMessage());
                        continue;
                    }
                    // build responsive images?
                    if ($this->config->isEnabled('pages.body.images.responsive')) {
                        try {
                            $srcset = Image::buildSrcset($assetConverted, $this->config->getAssetsImagesWidths());
                        } catch (\Exception $e) {
                            $this->builder->getLogger()->debug($e->getMessage());
                        }
                    }
                    // if not, use default image as srcset
                    if (empty($srcset)) {
                        $srcset = (string) $assetConverted;
                    }
                    // add format to <sources>
                    $sources[] = [
                        'name'       => 'source',
                        'attributes' => [
                            'type'   => "image/$format",
                            'srcset' => $srcset,
                            'sizes'  => $sizes,
                            'width'  => $InlineImage['element']['attributes']['width'],
                            'height' => $InlineImage['element']['attributes']['height'],
                        ],
                    ];
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
                    unset($image['element']['attributes']['title']); // @phpstan-ignore unset.offset
                    $picture['element']['text'][] = $image['element'];
                    $image = $picture;
                }
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
            }
        }

        // if title: put the <img> (or <picture>) in a <figure> and create a <figcaption>
        if ($this->config->isEnabled('pages.body.images.caption')) {
            return $this->createFigure($image);
        }

        return $image;
    }

    /**
     * Image block.
     */
    protected function blockImage($Excerpt)
    {
        if (1 !== preg_match($this->regexImage, $Excerpt['text'])) {
            return;
        }

        $InlineImage = $this->inlineImage($Excerpt);
        if (!isset($InlineImage)) {
            return;
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
     * Code inspired by https://github.com/sixlive/parsedown-alert from TJ Miller (@sixlive).
     */
    protected function blockNote($block)
    {
        if (preg_match('/:::(.*)/', $block['text'], $matches)) {
            $block = [
                'char'    => ':',
                'element' => [
                    'name'       => 'aside',
                    'text'       => '',
                    'attributes' => [
                        'class' => 'note',
                    ],
                ],
            ];
            if (!empty($matches[1])) {
                $block['element']['attributes']['class'] .= " note-{$matches[1]}";
            }

            return $block;
        }
    }

    protected function blockNoteContinue($line, $block)
    {
        if (isset($block['complete'])) {
            return;
        }
        if (preg_match('/:::/', $line['text'])) {
            $block['complete'] = true;

            return $block;
        }
        $block['element']['text'] .= $line['text'] . "\n";

        return $block;
    }

    protected function blockNoteComplete($block)
    {
        $block['element']['rawHtml'] = $this->text($block['element']['text']);
        unset($block['element']['text']);

        return $block;
    }

    /**
     * Apply Highlight to code blocks.
     */
    protected function blockFencedCodeComplete($block)
    {
        if (!$this->config->isEnabled('pages.body.highlight')) {
            return $block;
        }
        if (!isset($block['element']['text']['attributes'])) {
            return $block;
        }

        try {
            $code = $block['element']['text']['text'];
            $languageClass = $block['element']['text']['attributes']['class'];
            $language = explode('-', $languageClass);
            $highlighted = $this->highlighter->highlight($language[1], $code);
            $block['element']['text']['attributes']['class'] = vsprintf('%s hljs %s', [
                $languageClass,
                $highlighted->language,
            ]);
            $block['element']['text']['rawHtml'] = $highlighted->value;
            $block['element']['text']['allowRawHtmlInSafeMode'] = true;
            unset($block['element']['text']['text']);
        } catch (\Exception $e) {
            $this->builder->getLogger()->debug($e->getMessage());
        } finally {
            return $block;
        }
    }

    /**
     * {@inheritdoc}
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
                    $Data[$a] = trim($v, '"');
                }
            }
        }

        return $Data;
    }

    /**
     * {@inheritdoc}
     *
     * Converts XHTML '<br />' tag to '<br>'.
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
     * XHTML closing tag to HTML5 closing tag.
     *
     * @return string
     */
    protected function element(array $Element)
    {
        return str_replace(' />', '>', parent::element($Element)); // @phpstan-ignore staticMethod.notFound
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
     * Create a media (video or audio) element from a link.
     */
    private function createMediaFromLink(array $link, string $type = 'video'): array
    {
        $block = [
            'extent'  => $link['extent'],
            'element' => [
                'text' => $link['element']['text'],
            ],
        ];
        $block['element']['attributes'] = $link['element']['attributes'];
        unset($block['element']['attributes']['href']);
        $block['element']['attributes']['src'] = (string) new Asset($this->builder, $link['element']['attributes']['href'], ['leading_slash' => false]);
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
                    $block['element']['attributes']['poster'] = (string) new Asset($this->builder, $block['element']['attributes']['poster'], ['leading_slash' => false]);
                }
                if (!\array_key_exists('style', $block['element']['attributes'])) {
                    $block['element']['attributes']['style'] = '';
                }
                $block['element']['attributes']['style'] .= ';max-width:100%;height:auto;background-color: #d8d8d8;'; // background color if offline

                return $block;
            case 'audio':
                $block['element']['name'] = 'audio';

                return $block;
        }

        throw new \Exception(\sprintf('Can\'t create %s from "%s".', $type, $link['element']['attributes']['href']));
    }

    /**
     * Create a figure / caption element.
     */
    private function createFigure(array $inline): array
    {
        if (empty($inline['element']['attributes']['title'])) {
            return $inline;
        }

        $titleRawHtml = $this->line($inline['element']['attributes']['title']); // @phpstan-ignore method.notFound
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
