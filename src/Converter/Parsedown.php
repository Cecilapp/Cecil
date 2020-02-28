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

        $query = parse_url($image['element']['attributes']['src'], PHP_URL_QUERY);
        if ($query === null) {
            return $image;
        }
        // clean URL
        $image['element']['attributes']['src'] = strtok($image['element']['attributes']['src'], '?');

        // has resize value
        parse_str($query, $result);
        if (array_key_exists('resize', $result)) {
            $size = (int) $result['resize'];
            $image['element']['attributes']['width'] = $size;

            $image['element']['attributes']['src'] = (new Image($this->builder))
                ->resize($image['element']['attributes']['src'], $size);
        }

        return $image;
    }
}
