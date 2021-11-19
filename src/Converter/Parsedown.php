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
        $image['element']['attributes']['src'] = $imageSource = trim($this->removeQuery($image['element']['attributes']['src']));
        $asset = new Asset($this->builder, $imageSource);
        $image['element']['attributes']['src'] = $asset;
        $width = $asset->getWidth();
        /**
         * Should be lazy loaded?
         */
        if ($this->builder->getConfig()->get('content.images.lazy.enabled')) {
            $image['element']['attributes']['loading'] = 'lazy';
        }
        /**
         * Should be resized?
         */
        $imageResized = null;
        if (array_key_exists('width', $image['element']['attributes'])
            && (int) $image['element']['attributes']['width'] < $width
            && $this->builder->getConfig()->get('content.images.resize.enabled')
        ) {
            $width = (int) $image['element']['attributes']['width'];

            try {
                $imageResized = $asset->resize($width);
            } catch (\Exception $e) {
                $this->builder->getLogger()->debug($e->getMessage());

                return $image;
            }
            $image['element']['attributes']['src'] = $imageResized;
        }
        // set width
        if (!array_key_exists('width', $image['element']['attributes'])) {
            $image['element']['attributes']['width'] = $width;
        }
        /**
         * Should be responsive?
         */
        if ($this->builder->getConfig()->get('content.images.responsive.enabled')) {
            $steps = $this->builder->getConfig()->get('content.images.responsive.width.steps');
            $wMin = $this->builder->getConfig()->get('content.images.responsive.width.min');
            $wMax = $this->builder->getConfig()->get('content.images.responsive.width.max');
            $srcset = '';
            for ($i = 1; $i <= $steps; $i++) {
                $w = ceil($wMin * $i);
                if ($w > $width || $w > $wMax) {
                    break;
                }
                $a = new Asset($this->builder, $imageSource);
                $img = $a->resize($w);
                $srcset .= sprintf('%s %sw', $img, $w);
                if ($i < $steps) {
                    $srcset .= ', ';
                }
            }
            if ($imageResized) {
                $srcset .= sprintf(',%s %sw', $imageResized, $width);
            } else {
                $srcset .= sprintf(',%s %sw', $asset, $width);
            }
            // ie: srcset="/img-480.jpg 480w, /img-800.jpg 800w"
            $image['element']['attributes']['srcset'] = $srcset;
            $image['element']['attributes']['sizes'] = $this->builder->getConfig()->get('content.images.responsive.sizes.default');
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
        if (!empty($HtmlAtt)) {
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
