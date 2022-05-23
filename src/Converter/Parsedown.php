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

class Parsedown extends \ParsedownToC
{
    /** @var Builder */
    protected $builder;

    /** {@inheritdoc} */
    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

    /** Regex to verify there is an image in <figure> block */
    private $MarkdownImageRegex = "~^!\[.*?\]\(.*?\)~";

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

        // "insert" line block: ++text++ -> <ins>text</ins>
        $this->InlineTypes['+'][] = 'Insert';
        $this->inlineMarkerList = implode('', array_keys($this->InlineTypes));
        $this->specialCharacters[] = '+';
        // add caption to image block
        if ($this->builder->getConfig()->get('body.images.caption.enabled')) {
            $this->BlockTypes['!'][] = 'Image';
        }
        // "notes" block
        if ($this->builder->getConfig()->get('body.notes.enabled')) {
            $this->BlockTypes[':'][] = 'Note';
        }

        parent::__construct(['selectors' => $this->builder->getConfig()->get('body.toc')]);
    }

    /**
     * Insert inline.
     * e.g.: ++text++ -> <ins>text</ins>
     */
    protected function inlineInsert($Excerpt)
    {
        if (!isset($Excerpt['text'][1])) {
            return;
        }

        if ($Excerpt['text'][1] === '+' and preg_match('/^\+\+(?=\S)(.+?)(?<=\S)\+\+/', $Excerpt['text'], $matches)) {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'ins',
                    'text' => $matches[1],
                    'handler' => 'line',
                ),
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function inlineImage($excerpt)
    {
        $image = parent::inlineImage($excerpt);
        if (!isset($image)) {
            return null;
        }
        // clean source path / URL
        $image['element']['attributes']['src'] = trim($this->removeQuery($image['element']['attributes']['src']));
        // should be lazy loaded?
        if ($this->builder->getConfig()->get('body.images.lazy.enabled')) {
            $image['element']['attributes']['loading'] = 'lazy';
        }
        // disable image handling
        if (!$this->builder->getConfig()->get('body.images.remote.enabled') ?? true) {
            return $image;
        }
        // create asset
        $asset = new Asset($this->builder, $image['element']['attributes']['src'], ['force_slash' => false]);
        // get width
        $width = $asset->getWidth();
        $image['element']['attributes']['src'] = $asset;
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
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $image;
            }
            $image['element']['attributes']['src'] = $assetResized;
        }
        // set width
        if (!isset($image['element']['attributes']['width'])) {
            $image['element']['attributes']['width'] = $width;
        }
        // set height
        if (!isset($image['element']['attributes']['height'])) {
            $image['element']['attributes']['height'] = $asset->getHeight();
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
     * Enhanced image block with <figure>/<figcaption>.
     */
    protected function blockImage($Line)
    {
        if (1 !== preg_match($this->MarkdownImageRegex, $Line['text'])) {
            return;
        }

        $InlineImage = $this->inlineImage($Line);
        if (!isset($InlineImage)) {
            return;
        }

        $block = $InlineImage;

        /*
        <figure>
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
            <figcaption>...</figcaption>
        </figure>
        */

        // creates a <picture> element with a <source> (WebP) and an <img> element
        if (($this->builder->getConfig()->get('body.images.remote.enabled') ?? true) && ($this->builder->getConfig()->get('body.images.webp.enabled') ?? false) && !Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
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
        }

        // put <img> (or <picture>) in a <figure> element if there is a title (<figcaption>)
        if (!empty($InlineImage['element']['attributes']['title'])) {
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
     * Removes query string from URL.
     */
    private function removeQuery(string $path): string
    {
        return strtok($path, '?');
    }
}
