<?php
/**
 * This file is part of the Cecil/Cecil package.
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
        if ($this->builder->getConfig()->get('body.images.caption.enabled')) {
            $this->BlockTypes['!'][] = 'Image';
        }
        parent::__construct(['selectors' => $this->builder->getConfig()->get('body.toc')]);
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
        // create asset
        $asset = new Asset($this->builder, $image['element']['attributes']['src'], ['force_slash' => false]);
        // is asset is valid? (if yes get width)
        if (false === $width = $asset->getWidth()) {
            return $image;
        }
        $image['element']['attributes']['src'] = $asset;
        /**
         * Should be lazy loaded?
         */
        if ($this->builder->getConfig()->get('body.images.lazy.enabled')) {
            $image['element']['attributes']['loading'] = 'lazy';
        }
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
            if ($srcset = Image::getSrcset(
                $assetResized ?? $asset,
                $this->builder->getConfig()->get('assets.images.responsive.width.steps') ?? 5,
                $this->builder->getConfig()->get('assets.images.responsive.width.min') ?? 320,
                $this->builder->getConfig()->get('assets.images.responsive.width.max') ?? 1280
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

        // creates a <picture> element with <source> and <img> elements
        if (($this->builder->getConfig()->get('body.images.webp.enabled') ?? false) && !Image::isAnimatedGif($InlineImage['element']['attributes']['src'])) {
            $assetWebp = Image::convertTopWebp($InlineImage['element']['attributes']['src'], $this->builder->getConfig()->get('assets.images.quality') ?? 85);
            $srcset = Image::getSrcset(
                $assetWebp,
                $this->builder->getConfig()->get('assets.images.responsive.width.steps') ?? 5,
                $this->builder->getConfig()->get('assets.images.responsive.width.min') ?? 320,
                $this->builder->getConfig()->get('assets.images.responsive.width.max') ?? 1280
            );
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

        // put <img> or <picture> in a <figure> element if there is a title
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
     * Removes query string from URL.
     */
    private function removeQuery(string $path): string
    {
        return strtok($path, '?');
    }
}
