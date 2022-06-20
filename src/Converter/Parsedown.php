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

    /** Valid a media block (image, audio or video) */
    protected $MarkdownMediaRegex = "~^!\[.*?\]\(.*?\)~";

    /** @var Highlighter */
    protected $highlighter;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

        // "insert" line block: ++text++ -> <ins>text</ins>
        $this->InlineTypes['+'][] = 'Insert';
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));
        $this->specialCharacters[] = '+';

        // Media (image, audio or video) block
        $this->BlockTypes['!'][] = 'Media';

        // "notes" block
        $this->BlockTypes[':'][] = 'Note';

        // code highlight
        $this->highlighter = new Highlighter();

        // Table of Content
        parent::__construct(['selectors' => $this->builder->getConfig()->get('body.toc')]);
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
    protected function inlineImage($Excerpt)
    {
        $image = parent::inlineImage($Excerpt);
        if (!isset($image)) {
            return null;
        }

        // clean source path / URL
        $image['element']['attributes']['src'] = $this->cleanUrl($image['element']['attributes']['src']);

        // should be lazy loaded?
        if ($this->builder->getConfig()->get('body.images.lazy.enabled')) {
            $image['element']['attributes']['loading'] = 'lazy';
        }

        // disable remote image handling?
        if (Util\Url::isUrl($image['element']['attributes']['src']) && !$this->builder->getConfig()->get('body.images.remote.enabled') ?? true) {
            return $image;
        }

        // create asset
        $asset = new Asset($this->builder, $image['element']['attributes']['src'], ['force_slash' => false]);
        $image['element']['attributes']['src'] = $asset;
        $width = $asset->getWidth();

        /**
         * Should be resized?
         */
        $assetResized = null;
        if (isset($image['element']['attributes']['width'])
            && (int) $image['element']['attributes']['width'] < $width
            && $this->builder->getConfig()->get('body.images.resize.enabled')
        ) {
            $width = (int) $image['element']['attributes']['width'];

            try {
                $assetResized = $asset->resize($width);
                $image['element']['attributes']['src'] = $assetResized;
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $image;
            }
        }

        // set width
        if (!isset($image['element']['attributes']['width']) && $asset['type'] == 'image') {
            $image['element']['attributes']['width'] = $width;
        }
        // set height
        if (!isset($image['element']['attributes']['height']) && $asset['type'] == 'image') {
            $image['element']['attributes']['height'] = ($assetResized ?? $asset)->getHeight();
        }

        /**
         * Should be responsive?
         */
        if ($this->builder->getConfig()->get('body.images.responsive.enabled')) {
            if ($srcset = Image::buildSrcset(
                $assetResized ?? $asset,
                $this->builder->getConfig()->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
            )) {
                $image['element']['attributes']['srcset'] = $srcset;
                $image['element']['attributes']['sizes'] = $this->builder->getConfig()->get('assets.images.responsive.sizes.default');
            }
        }

        return $image;
    }

    /**
     * Media block support:
     * 1. <picture>/<source> for WebP images
     * 2. <audio> and <video> elements
     * 3. <figure>/<figcaption> for element with a title.
     */
    protected function blockMedia($Excerpt)
    {
        if (1 !== preg_match($this->MarkdownMediaRegex, $Excerpt['text'])) {
            return;
        }

        $InlineImage = $this->inlineImage($Excerpt);
        if (!isset($InlineImage)) {
            return;
        }
        $block = $InlineImage;

        switch ($block['element']['attributes']['alt']) {
            case 'audio':
                $audio = [];
                $audio['element'] = [
                    'name'    => 'audio',
                    'handler' => 'element',
                ];
                $audio['element']['attributes'] = ['controls' => '', 'preload' => 'none'] + $block['element']['attributes'];
                unset($audio['element']['attributes']['loading']);
                $block = $audio;
                break;
            case 'video':
                $video = [];
                $video['element'] = [
                    'name'       => 'video',
                    'handler'    => 'element',
                ];
                $video['element']['attributes'] = ['controls' => '', 'preload' => 'none'] + $block['element']['attributes'];
                unset($video['element']['attributes']['loading']);
                if (isset($block['element']['attributes']['poster'])) {
                    $video['element']['attributes']['poster'] = new Asset($this->builder, $block['element']['attributes']['poster'], ['force_slash' => false]);
                }
                $block = $video;
            }

        /*
        <!-- if image has a title: a <figure> is required for <figcaption> -->
        <figure>
            <!-- if WebP: a <picture> is required for <source> -->
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
            <!-- title -->
            <figcaption>...</figcaption>
        </figure>
        */

        // creates a <picture> used to add WebP <source> in addition to the image <img> element
        if ($this->builder->getConfig()->get('body.images.webp.enabled') ?? false
            && ($InlineImage['element']['attributes']['src'])['type'] == 'image'
            && ($InlineImage['element']['attributes']['src'])['subtype'] != 'image/webp') {
            try {
                // Image src must be an Asset instance
                if (is_string($InlineImage['element']['attributes']['src'])) {
                    throw new RuntimeException(\sprintf('Asset "%s" can\'t be converted to WebP', $InlineImage['element']['attributes']['src']));
                }
                // Image asset is an animated GIF
                if (Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
                    throw new RuntimeException(\sprintf('Asset "%s" is an animated GIF and can\'t be converted to WebP', $InlineImage['element']['attributes']['src']));
                }
                $assetWebp = Image::convertTopWebp($InlineImage['element']['attributes']['src'], $this->builder->getConfig()->get('assets.images.quality') ?? 75);
                $srcset = '';
                if ($this->builder->getConfig()->get('body.images.responsive.enabled')) {
                    $srcset = Image::buildSrcset(
                        $assetWebp,
                        $this->builder->getConfig()->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920]
                    );
                }
                if (empty($srcset)) {
                    $srcset = (string) $assetWebp;
                }
                $PictureBlock = [
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
                            'sizes'  => $this->builder->getConfig()->get('assets.images.responsive.sizes.default'),
                        ],
                    ],
                ];
                $PictureBlock['element']['text'][] = $source['element'];
                $PictureBlock['element']['text'][] = $InlineImage['element'];
                $block = $PictureBlock;
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());
            }
        }

        // if there is a title: put the <img> (or <picture>) in a <figure> element to use the <figcaption>
        if ($this->builder->getConfig()->get('body.images.caption.enabled') && !empty($InlineImage['element']['attributes']['title'])) {
            $FigureBlock = [
                'element' => [
                    'name'    => 'figure',
                    'handler' => 'elements',
                    'text'    => [
                        $block['element'],
                    ],
                ],
            ];
            $InlineFigcaption = [
                'element' => [
                    'name' => 'figcaption',
                    'text' => $InlineImage['element']['attributes']['title'],
                ],
            ];
            $FigureBlock['element']['text'][] = $InlineFigcaption['element'];

            return $FigureBlock;
        }

        return $block;
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
            return [
                'char'    => ':',
                'element' => [
                    'name'       => 'aside',
                    'text'       => '',
                    'attributes' => [
                        'class' => "note note-{$matches[1]}",
                    ],
                ],
            ];
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

        $code = $block['element']['text']['text'];
        unset($block['element']['text']['text']);
        $languageClass = $block['element']['text']['attributes']['class'];
        $language = explode('-', $languageClass);
        $highlighted = $this->highlighter->highlight($language[1], $code);
        $block['element']['text']['attributes']['class'] = vsprintf('%s hljs %s', [
            $languageClass,
            $highlighted->language,
        ]);
        $block['element']['text']['rawHtml'] = $highlighted->value;
        $block['element']['text']['allowRawHtmlInSafeMode'] = true;

        return $block;
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
     * Returns URL without query string.
     */
    private function cleanUrl(string $path): string
    {
        return strtok(trim($path), '?') ?: trim($path);
    }
}
