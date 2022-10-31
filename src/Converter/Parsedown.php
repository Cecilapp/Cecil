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

namespace Cecil\Converter;

use Cecil\Assets\Asset;
use Cecil\Assets\Image;
use Cecil\Builder;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Highlight\Highlighter;

class Parsedown extends \ParsedownToC
{
    /** @var Builder */
    protected $builder;

    /** {@inheritdoc} */
    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

    /** @var Highlighter */
    protected $highlighter;

    public function __construct(Builder $builder, ?array $options = null)
    {
        $this->builder = $builder;

        // "insert" line block: ++text++ -> <ins>text</ins>
        $this->InlineTypes['+'][] = 'Insert';
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));
        $this->specialCharacters[] = '+';

        // "notes" block
        $this->BlockTypes[':'][] = 'Note';

        // code highlight
        $this->highlighter = new Highlighter();

        // options
        $options = array_merge(['selectors' => $this->builder->getConfig()->get('body.toc')], $options ?? []);

        parent::__construct($options);
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
                'extent'  => strlen($matches[0]),
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
        $link = parent::inlineLink($Excerpt);

        if (!isset($link)) {
            return null;
        }

        // Link to a page with "page:page_id" as URL
        if (Util\Str::startsWith($link['element']['attributes']['href'], 'page:')) {
            $link['element']['attributes']['href'] = new \Cecil\Assets\Url($this->builder, substr($link['element']['attributes']['href'], 5, strlen($link['element']['attributes']['href'])));

            return $link;
        }

        /*
         * Embed link?
         */
        $embed = false;
        $embed = (bool) $this->builder->getConfig()->get('body.links.embed.enabled') ?? false;
        if (isset($link['element']['attributes']['embed'])) {
            $embed = true;
            if ($link['element']['attributes']['embed'] == 'false') {
                $embed = false;
            }
            unset($link['element']['attributes']['embed']);
        }
        if (!$embed) {
            return $link;
        }
        // video or audio?
        $extension = pathinfo($link['element']['attributes']['href'], PATHINFO_EXTENSION);
        if (in_array($extension, $this->builder->getConfig()->get('body.links.embed.video.ext'))) {
            return $this->createMediaFromLink($link, 'video');
        }
        if (in_array($extension, $this->builder->getConfig()->get('body.links.embed.audio.ext'))) {
            return $this->createMediaFromLink($link, 'audio');
        }
        // GitHub Gist link?
        // https://regex101.com/r/QmCiAL/1
        $pattern = 'https:\/\/gist\.github.com\/[-a-zA-Z0-9_]+\/[-a-zA-Z0-9_]+';
        if (preg_match('/'.$pattern.'/is', (string) $link['element']['attributes']['href'], $matches)) {
            return [
                'extent'  => $link['extent'],
                'element' => [
                    'name'       => 'script',
                    'text'       => $link['element']['text'],
                    'attributes' => [
                        'src' => $matches[0].'.js',
                    ],
                ],
            ];
        }
        // Youtube link?
        // https://regex101.com/r/gznM1j/1
        $pattern = '(?:https?:\/\/)?(?:www\.)?youtu(?:\.be\/|be.com\/\S*(?:watch|embed)(?:(?:(?=\/[-a-zA-Z0-9_]{11,}(?!\S))\/)|(?:\S*v=|v\/)))([-a-zA-Z0-9_]{11,})';
        if (preg_match('/'.$pattern.'/is', (string) $link['element']['attributes']['href'], $matches)) {
            $iframe = [
                'element' => [
                    'name'       => 'iframe',
                    'text'       => $link['element']['text'],
                    'attributes' => [
                        'width'           => '560',
                        'height'          => '315',
                        'title'           => $link['element']['text'],
                        'src'             => 'https://www.youtube.com/embed/'.$matches[1],
                        'frameborder'     => '0',
                        'allow'           => 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture',
                        'allowfullscreen' => '',
                        'style'           => 'position:absolute; top:0; left:0; width:100%; height:100%; border:0',
                    ],
                ],
            ];

            return [
                'extent'  => $link['extent'],
                'element' => [
                    'name'    => 'div',
                    'handler' => 'elements',
                    'text'    => [
                        $iframe['element'],
                    ],
                    'attributes' => [
                        'style' => 'position:relative; padding-bottom:56.25%; height:0; overflow:hidden',
                    ],
                ],
            ];
        }

        return $link;
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineImage($Excerpt)
    {
        $InlineImage = parent::inlineImage($Excerpt);
        if (!isset($InlineImage)) {
            return null;
        }

        // remove quesry string
        $InlineImage['element']['attributes']['src'] = $this->removeQueryString($InlineImage['element']['attributes']['src']);

        // normalize path
        $InlineImage['element']['attributes']['src'] = $this->normalizePath($InlineImage['element']['attributes']['src']);

        // should be lazy loaded?
        if ($this->builder->getConfig()->get('body.images.lazy.enabled') && !isset($InlineImage['element']['attributes']['loading'])) {
            $InlineImage['element']['attributes']['loading'] = 'lazy';
        }

        // add default class?
        if ($this->builder->getConfig()->get('body.images.class')) {
            $InlineImage['element']['attributes']['class'] .= ' '.$this->builder->getConfig()->get('body.images.class');
            $InlineImage['element']['attributes']['class'] = trim($InlineImage['element']['attributes']['class']);
        }

        // disable remote image handling?
        if (Util\Url::isUrl($InlineImage['element']['attributes']['src']) && !$this->builder->getConfig()->get('body.images.remote.enabled') ?? true) {
            return $InlineImage;
        }

        // create asset
        $assetOptions = ['force_slash' => false];
        if ($this->builder->getConfig()->get('body.images.remote.fallback.enabled')) {
            $assetOptions += ['remote_fallback' => $this->builder->getConfig()->get('body.images.remote.fallback.path')];
        }
        $asset = new Asset($this->builder, $InlineImage['element']['attributes']['src'], $assetOptions);
        $InlineImage['element']['attributes']['src'] = $asset;
        $width = $asset->getWidth();

        /*
         * Should be resized?
         */
        $assetResized = null;
        if (isset($InlineImage['element']['attributes']['width'])
            && (int) $InlineImage['element']['attributes']['width'] < $width
            && $this->builder->getConfig()->get('body.images.resize.enabled')
        ) {
            $width = (int) $InlineImage['element']['attributes']['width'];

            try {
                $assetResized = $asset->resize($width);
                $InlineImage['element']['attributes']['src'] = $assetResized;
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $InlineImage;
            }
        }

        // set width
        if (!isset($InlineImage['element']['attributes']['width'])) {
            $InlineImage['element']['attributes']['width'] = $width;
        }
        // set height
        if (!isset($InlineImage['element']['attributes']['height'])) {
            $InlineImage['element']['attributes']['height'] = ($assetResized ?? $asset)->getHeight();
        }

        /*
         * Should be responsive?
         */
        $sizes = '';
        if ($this->builder->getConfig()->get('body.images.responsive.enabled')) {
            try {
                if ($srcset = Image::buildSrcset(
                    $assetResized ?? $asset,
                    $this->builder->getConfig()->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
                )) {
                    $InlineImage['element']['attributes']['srcset'] = $srcset;
                    $sizes = (string) $this->builder->getConfig()->get('assets.images.responsive.sizes.default');
                    if (isset($InlineImage['element']['attributes']['class'])) {
                        $sizes = Image::getSizes($InlineImage['element']['attributes']['class'], (array) $this->builder->getConfig()->get('assets.images.responsive.sizes'));
                    }
                    $InlineImage['element']['attributes']['sizes'] = $sizes;
                }
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
            }
        }

        /*
        <!-- if title: a <figure> is required to put in it a <figcaption> -->
        <figure>
            <!-- if WebP is enabled: a <picture> is required for the WebP <source> -->
            <picture>
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

        /*
         * if title:
         *   1. converts Markdown and store it into raw HTML
         *   2. clean title attribute
         */
        $titleRawHtml = '';
        if (isset($InlineImage['element']['attributes']['title'])) {
            $titleRawHtml = $this->line($InlineImage['element']['attributes']['title']);
            $InlineImage['element']['attributes']['title'] = strip_tags($titleRawHtml);
        }

        $image = $InlineImage;

        // converts image to WebP and put it in picture > source
        if ($this->builder->getConfig()->get('body.images.webp.enabled') ?? false
            && (($InlineImage['element']['attributes']['src'])['type'] == 'image'
                && ($InlineImage['element']['attributes']['src'])['subtype'] != 'image/webp')
        ) {
            try {
                // InlineImage src must be an Asset instance
                if (!$InlineImage['element']['attributes']['src'] instanceof Asset) {
                    throw new RuntimeException(\sprintf('Asset "%s" can\'t be converted to WebP', $InlineImage['element']['attributes']['src']));
                }
                // abord if InlineImage is an animated GIF
                if (Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
                    throw new RuntimeException(\sprintf('Asset "%s" is an animated GIF and can\'t be converted to WebP', $InlineImage['element']['attributes']['src']));
                }
                $assetWebp = Image::convertTopWebp($InlineImage['element']['attributes']['src'], $this->builder->getConfig()->get('assets.images.quality') ?? 75);
                $srcset = '';
                // build responsives WebP?
                if ($this->builder->getConfig()->get('body.images.responsive.enabled')) {
                    try {
                        $srcset = Image::buildSrcset(
                            $assetWebp,
                            $this->builder->getConfig()->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
                        );
                    } catch (\Exception $e) {
                        $this->builder->getLogger()->debug($e->getMessage());
                    }
                }
                // if not, default image as srcset
                if (empty($srcset)) {
                    $srcset = (string) $assetWebp;
                }
                $picture = [
                    'extent'  => $InlineImage['extent'],
                    'element' => [
                        'name'    => 'picture',
                        'handler' => 'elements',
                    ],
                ];
                $source = [
                    'element' => [
                        'name'       => 'source',
                        'attributes' => [
                            'type'   => 'image/webp',
                            'srcset' => $srcset,
                            'sizes'  => $sizes,
                        ],
                    ],
                ];
                $picture['element']['text'][] = $source['element'];
                $picture['element']['text'][] = $image['element'];
                $image = $picture;
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
            }
        }

        // if title: put the <img> (or <picture>) in a <figure> and create a <figcaption>
        if (!empty($titleRawHtml) && $this->builder->getConfig()->get('body.images.caption.enabled')) {
            $figure = [
                'extent'  => $InlineImage['extent'],
                'element' => [
                    'name'    => 'figure',
                    'handler' => 'elements',
                    'text'    => [
                        $image['element'],
                    ],
                ],
            ];
            $figcaption = [
                'element' => [
                    'name'                   => 'figcaption',
                    'allowRawHtmlInSafeMode' => true,
                    'rawHtml'                => $this->line($titleRawHtml),
                ],
            ];
            $figure['element']['text'][] = $figcaption['element'];

            return $figure;
        }

        return $image;
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
        $block['element']['text'] .= $line['text']."\n";

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
        if (!$this->builder->getConfig()->get('body.highlight.enabled')) {
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

        return $Data;
    }

    /**
     * Remove query string form a path/URL.
     */
    private function removeQueryString(string $path): string
    {
        return strtok(trim($path), '?') ?: trim($path);
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
            $this->builder->getConfig()->get('static.dir'),
            $this->builder->getConfig()->get('assets.dir')
        );
        $path = Util::joinPath($path);
        if (!preg_match('/'.$pattern.'/is', $path, $matches)) {
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
                'handler' => 'element',
            ],
        ];
        $block['element']['attributes'] = $link['element']['attributes'];
        unset($block['element']['attributes']['href']);
        $block['element']['attributes']['src'] = (string) new Asset($this->builder, $link['element']['attributes']['href'], ['force_slash' => false]);
        switch ($type) {
            case 'video':
                $block['element']['name'] = 'video';
                if (isset($link['element']['attributes']['poster'])) {
                    $block['element']['attributes']['poster'] = (string) new Asset($this->builder, $link['element']['attributes']['poster'], ['force_slash' => false]);
                }

                return $block;
            case 'audio':
                $block['element']['name'] = 'audio';

                return $block;
        }

        throw new \Exception(\sprintf('Can\'t create %s from "%s".', $type, $link['element']['attributes']['href']));
    }
}
