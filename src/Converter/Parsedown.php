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
    private $builder;

    /**
     * {@inheritdoc}
     */
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
        $asset = new Asset($this->builder, ltrim($this->removeQuery($image['element']['attributes']['src'])));
        // fetch image properties
        $width = $asset->getWidth();
        // sets default attributes
        $image['element']['attributes']['width'] = $width;
        $image['element']['attributes']['loading'] = 'lazy';
        // captures query string.
        // ie: "?resize=300&responsive"
        $query = parse_url($image['element']['attributes']['src'], PHP_URL_QUERY);
        if ($query === null) {
            return $image;
        }
        parse_str($query, $result);
        // cleans URL
        $image['element']['attributes']['src'] = $this->removeQuery($image['element']['attributes']['src']);
        /**
         * Should be responsive?
         */
        if (array_key_exists('responsive', $result) || $this->builder->getConfig()->get('assets.images.responsive')) {
            $steps = 5;
            $wMin = 320;
            $wMax = 2560;
            if ($width < $wMax) {
                $wMax = $width;
            }
            $srcset = '';
            for ($i = 1; $i <= $steps; $i++) {
                $w = (int) ceil($wMin + ($wMax - $wMin) / $steps * $i);
                $a = new Asset($this->builder, ltrim($this->removeQuery($image['element']['attributes']['src'])));
                $img = $a->resize($w);
                $srcset .= sprintf('%s %sw', $img, $w);
                if ($i < $steps) {
                    $srcset .= ', ';
                }
            }
            // ie: srcset="/img-480.jpg 480w, /img-800.jpg 800w"
            $image['element']['attributes']['srcset'] = $srcset;
            $image['element']['attributes']['sizes'] = '100vw';
        }
        /**
         * Should be resized?
         */
        if (array_key_exists('resize', $result)) {
            $width = (int) $result['resize'];
            $imageResized = $asset->resize($width);
            $image['element']['attributes']['src'] = $imageResized;
            $image['element']['attributes']['width'] = $width;
        }
        // set 'class' attribute
        if (array_key_exists('class', $result)) {
            $class = $result['class'];
            $class = strtr($class, ',', ' ');
            $image['element']['attributes']['class'] = $class;
        }

        return $image;
    }

    /**
     * Removes query string from URL.
     */
    private function removeQuery(string $path): string
    {
        return strtok($path, '?');
    }
}
