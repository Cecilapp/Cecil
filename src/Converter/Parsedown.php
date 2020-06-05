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

use Cecil\Assets\Image;
use Cecil\Builder;
use Cecil\Util;
use ParsedownExtra;

class Parsedown extends ParsedownExtra
{
    /** @var Builder */
    private $builder;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        parent::__construct();
        $this->builder = $builder;
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

        $path = Util::joinFile(
            $this->builder->getConfig()->getStaticTargetPath(),
            ltrim($image['element']['attributes']['src'])
        );
        if (Util::isExternalUrl($image['element']['attributes']['src'])) {
            $path = $image['element']['attributes']['src'];
        }
        $size = getimagesize($this->removeQuery($path));
        $width = $size[0];
        $type = $size[2];

        // sets default attributes
        $image['element']['attributes']['width'] = $width;
        if ($type !== null) {
            $image['element']['attributes']['loading'] = 'lazy';
        }

        // captures query string.
        // ie: "?resize=300&responsive"
        $query = parse_url($image['element']['attributes']['src'], PHP_URL_QUERY);
        if ($query === null) {
            return $image;
        }
        parse_str($query, $result);
        // cleans URL
        $image['element']['attributes']['src'] = $this->removeQuery($image['element']['attributes']['src']);

        $path = Util::joinFile(
            $this->builder->getConfig()->getStaticTargetPath(),
            ltrim($image['element']['attributes']['src'])
        );
        if (Util::isExternalUrl($image['element']['attributes']['src'])) {
            $path = $image['element']['attributes']['src'];
        }
        list($width) = getimagesize($path);

        /**
         * Should be responsive?
         */
        $responsive = false;
        if (array_key_exists('responsive', $result) && !Util::isExternalUrl($image['element']['attributes']['src'])) {
            $responsive = true;
            // process
            $steps = 5;
            $wMin = 320;
            $wMax = 2560;
            if ($width < $wMax) {
                $wMax = $width;
            }
            $srcset = '';
            for ($i = 1; $i <= $steps; $i++) {
                $w = (int) ceil($wMin + ($wMax - $wMin) / $steps * $i);
                $img = (new Image($this->builder))->resize($image['element']['attributes']['src'], $w);
                $srcset .= sprintf('%s %sw', $img, $w);
                if ($i < $steps) {
                    $srcset .= ', ';
                }
            }
            // ie: srcset="/img-480.jpg 480w, img-800.jpg 800w"
            $image['element']['attributes']['srcset'] = $srcset;
        }

        /**
         * Should be resized?
         */
        if (array_key_exists('resize', $result)) {
            $size = (int) $result['resize'];
            $width = $size;

            $imageResized = (new Image($this->builder))
                ->resize($image['element']['attributes']['src'], $size);

            if (Util::isExternalUrl($image['element']['attributes']['src'])) {
                return $image;
            }

            $image['element']['attributes']['src'] = $imageResized;

            $path = Util::joinPath(
                $this->builder->getConfig()->getOutputPath(),
                ltrim($image['element']['attributes']['src'])
            );

            list($width) = getimagesize($path);
            $image['element']['attributes']['width'] = $width;
        }

        // if responsive: set 'sizes' attribute
        if ($responsive) {
            // sizes="(max-width: 2800px) 100vw, 2800px"
            $image['element']['attributes']['sizes'] = sprintf('(max-width: %spx) 100vw, %spx', $width, $width);
        }

        // set 'class' attribute
        if (array_key_exists('class', $result)) {
            $class = $result['class'];
            $class = strtr($class, ',', ' ');
            $image['element']['attributes']['class'] = $class;
        }

        return $image;
    }

    private function removeQuery(string $path): string
    {
        return strtok($path, '?');
    }
}
