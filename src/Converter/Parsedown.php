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

    public function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    protected function inlineImage($excerpt)
    {
        $save = true;

        $image = parent::inlineImage($excerpt);

        if (!isset($image)) {
            return null;
        }

        preg_match(
            '/'.self::PATTERN.'/s',
            $image['element']['attributes']['src'],
            $matches
        );

        if (empty($matches)) {
            return $image;
        }

        $image['element']['attributes']['src'] = $matches[1];

        if ($this->config === null) {
            return $image;
        }

        if (array_key_exists(3, $matches) && $matches[3] == 'resize') {
            $resize = $matches[4];

            $image['element']['attributes']['width'] = $resize;

            if (extension_loaded('gd')) {
                $img = Image::make($this->config->getStaticPath().'/'.$image['element']['attributes']['src'])
                    ->resize($resize, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                if ($save) {
                    Util::getFS()->mkdir($this->config->getOutputPath().'/assets/thumbs/'.$resize);
                    $img->save($this->config->getOutputPath().'/assets/thumbs/'.$resize.$image['element']['attributes']['src']);
                    $image['element']['attributes']['src'] = '/assets/thumbs/'.$resize.$image['element']['attributes']['src'];
                }
            }
        }

        return $image;
    }
}
