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
        if (array_key_exists('responsive', $result) && !Util::isExternalUrl($image['element']['attributes']['src'])) {
            $path = $this->builder->getConfig()->getStaticPath().'/'.ltrim($image['element']['attributes']['src'], '/');
            list($width) = getimagesize($path);
            $image['element']['attributes']['srcset'] = sprintf(
                '%s %sw, %s %sw, %s %sw',
                (new Image($this->builder))->resize($image['element']['attributes']['src'], ceil($width / 2)),
                ceil($width / 2),
                (new Image($this->builder))->resize($image['element']['attributes']['src'], ceil($width / 1.5)),
                ceil($width / 1.5),
                (new Image($this->builder))->resize($image['element']['attributes']['src'], $width),
                ceil($width)
            );
            $image['element']['attributes']['sizes'] = sprintf(
                '(max-width: %spx) %spx, %spx',
                ceil($width / 1.5),
                ceil($width / 2),
                ceil($width)
            );
        }

        // has resize value?
        if (array_key_exists('resize', $result)) {
            $size = (int) $result['resize'];
            $image['element']['attributes']['width'] = $size;
            $image['element']['attributes']['src'] = (new Image($this->builder))
                ->resize($image['element']['attributes']['src'], $size);
        }

        return $image;
    }
}
