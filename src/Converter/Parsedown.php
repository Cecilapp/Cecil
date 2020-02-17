<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

use Cecil\Config;
use Cecil\Util;
use Intervention\Image\ImageManagerStatic as Image;
use ParsedownExtra;

class Parsedown extends ParsedownExtra
{
    // https://regex101.com/r/EhIh5N/2
    const PATTERN = '(.*)(\?|\&)([^=]+)\=([^&]+)';
    private $config;

    function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    protected function inlineImage($excerpt)
    {
        $image = parent::inlineImage($excerpt);

        if (!isset($image)) {
            return null;
        }

        preg_match(
            '/'.self::PATTERN.'/s',
            $image['element']['attributes']['src'],
            $matches
        );

        if (!$matches) {
            return $image;
        }

        if (key_exists(3, $matches) && $matches[3] == 'resize') {
            $image['element']['attributes']['src'] = $matches[1];
            $resize = $matches[4];
        }

        if ($this->config === null) {
            return $image;
        }

        Util::getFS()->mkdir($this->config->getOutputPath().'/assets');

        Image::make($this->config->getStaticPath().'/'.$image['element']['attributes']['src'])
            ->resize($resize, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save($this->config->getOutputPath(). '/assets'.$image['element']['attributes']['src']);

        $image['element']['attributes']['src'] = '/assets'.$image['element']['attributes']['src'];

        return $image;
    }
}
