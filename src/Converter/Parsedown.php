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
use Cecil\Builder;

class Parsedown extends \ParsedownToC
{
    /** @var Builder */
    protected $builder;

    /** {@inheritdoc} */
    protected $regexAttribute = '(?:[#.][-\w:\\\]+[ ]*|[-\w:\\\]+(?:=(?:["\'][^\n]*?["\']|[^\s]+)?)?[ ]*)';

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
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
        $image['element']['attributes']['src'] = trim($this->removeQuery($image['element']['attributes']['src']));
        $asset = new Asset($this->builder, $image['element']['attributes']['src']);
        $width = $asset->getWidth();
        /**
         * Should be lazy loaded?
         */
        if ($this->builder->getConfig()->get('content.images.lazy.enabled')) {
            $image['element']['attributes']['loading'] = 'lazy';
        }
        /**
         * Should be responsive?
         */
        if ($this->builder->getConfig()->get('content.images.responsive.enabled')) {
            $steps = $this->builder->getConfig()->get('content.images.responsive.width.steps');
            $wMin = $this->builder->getConfig()->get('content.images.responsive.width.min');
            $wMax = $this->builder->getConfig()->get('content.images.responsive.width.max');
            if ($width < $wMax) {
                $wMax = $width;
            }
            $srcset = '';
            for ($i = 1; $i <= $steps; $i++) {
                $w = (int) ceil($wMin + ($wMax - $wMin) / $steps * $i);
                $a = new Asset($this->builder, $image['element']['attributes']['src']);
                $img = $a->resize($w);
                $srcset .= sprintf('%s %sw', $img, $w);
                if ($i < $steps) {
                    $srcset .= ', ';
                }
            }
            // ie: srcset="/img-480.jpg 480w, /img-800.jpg 800w"
            $image['element']['attributes']['srcset'] = $srcset;
            $image['element']['attributes']['sizes'] = $this->builder->getConfig()->get('content.images.responsive.sizes.default');
        }
        /**
         * Should be resized?
         */
        if ($image['element']['attributes']['width'] !== null
            && (int) $image['element']['attributes']['width'] < $width
            && $this->builder->getConfig()->get('content.images.resize.enabled')
        ) {
            $width = (int) $image['element']['attributes']['width'];
            $imageResized = $asset->resize($width);
            $image['element']['attributes']['src'] = $imageResized;
        }
        if ($image['element']['attributes']['width'] === null) {
            $image['element']['attributes']['width'] = $width;
        }

        return $image;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAttributeData($attributeString)
    {
        $Data = [];

        $attributes = preg_split('/[ ]+/', $attributeString, -1, PREG_SPLIT_NO_EMPTY);

        $HtmlAtt = [];

        foreach ($attributes as $attribute) {
            if ($attribute[0] === '#') {
                $Data['id'] = substr($attribute, 1);
            } elseif ($attribute[0] === '.') {
                $classes[] = substr($attribute, 1);
            } else {
                parse_str($attribute, $parsed);
                $HtmlAtt = array_merge($HtmlAtt, $parsed);
            }
        }

        if (isset($classes)) {
            $Data['class'] = implode(' ', $classes);
        }
        if (isset($HtmlAtt)) {
            foreach ($HtmlAtt as $a => $v) {
                $Data[$a] = trim($v, '"');
            }
        }

        return $Data;
    }

    /**
     * Removes query string from URL.
     */
    private function removeQuery(string $path): string
    {
        return strtok($path, '?');
    }
}
