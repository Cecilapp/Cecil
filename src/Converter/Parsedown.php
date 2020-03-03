<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
    /**
     * @var Builder
     */
    private $builder;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder = null)
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

        // capture query string. ie: "?resize=300&responsive"
        $query = parse_url($image['element']['attributes']['src'], PHP_URL_QUERY);
        if ($query === null) {
            return $image;
        }
        parse_str($query, $result);
        // clean URL
        $image['element']['attributes']['src'] = strtok($image['element']['attributes']['src'], '?');

        // should be responsive?
        $responsive = false;
        if (array_key_exists('responsive', $result) && !Util::isExternalUrl($image['element']['attributes']['src'])) {
            $responsive = true;
            $path = $this->builder->getConfig()->getStaticPath().'/'.ltrim($image['element']['attributes']['src'], '/');
            list($width) = getimagesize($path);
            // process
            $steps = 5;
            $wMin = 320;
            $wMax = 2560;
            if ($width < $wMax) {
                $wMax = $width;
            }
            $srcset = '';
            for ($i = 1; $i <= $steps; $i++) {
                $w = ceil($wMin + ($wMax - $wMin) / $steps * $i);
                $img = (new Image($this->builder))->resize($image['element']['attributes']['src'], $w);
                $srcset .= sprintf('%s %sw', $img, $w);
                if ($i < $steps) {
                    $srcset .= ', ';
                }
            }
            // srcset="/img-480.jpg 480w, img-800.jpg 800w"
            $image['element']['attributes']['srcset'] = $srcset;
        }

        // has resize value?
        if (array_key_exists('resize', $result)) {
            $size = (int) $result['resize'];
            $width = $size;
            $image['element']['attributes']['width'] = $width;
            $image['element']['attributes']['src'] = (new Image($this->builder))
                ->resize($image['element']['attributes']['src'], $size);
        }

        // set sizes
        if ($responsive) {
            // sizes="(max-width: 2800px) 100vw, 2800px"
            $image['element']['attributes']['sizes'] = sprintf('(max-width: %spx) 100vw, %spx', $width, $width);
        }

        return $image;
    }
}
