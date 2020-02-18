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
    const TMP_DIR = '.cecil';
    const PATTERN = '(.*)(\?|\&)([^=]+)\=([^&]+)'; // https://regex101.com/r/EhIh5N/2
    private $config;

    public function __construct(Config $config = null)
    {
        $this->config = $config;
    }

    protected function inlineImage($excerpt)
    {
        $save = true;
        $external = false;

        $image = parent::inlineImage($excerpt);

        if (!isset($image)) {
            return null;
        }

        if (preg_match('~^(?:f|ht)tps?://~i', $image['element']['attributes']['src'])) {
            $external = true;
        }

        preg_match('/'.self::PATTERN.'/s', $image['element']['attributes']['src'], $matches);
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
                if ($external) {
                    $img = Image::make($image['element']['attributes']['src']);
                } else {
                    $img = Image::make($this->config->getStaticPath().'/'.$image['element']['attributes']['src']);
                }
                $img->resize($resize, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                if ($save) {
                    if ($external) {
                        $imgPath = (string) $img->encode('data-url');
                    } else {
                        $imgPath = '/'.self::TMP_DIR.'/images/thumbs/'.$resize.$image['element']['attributes']['src'];
                        $dir = Util::getFS()->makePathRelative(
                            dirname($imgPath),
                            '/'.self::TMP_DIR.'/images/thumbs/'.$resize
                        );
                        Util::getFS()->mkdir($this->config->getDestinationDir().'/'.self::TMP_DIR.'/images/thumbs/'.$resize.'/'.$dir);
                        $img->save($this->config->getDestinationDir().$imgPath);
                        $imgPath = '/images/thumbs/'.$resize.$image['element']['attributes']['src'];
                    }
                    $image['element']['attributes']['src'] = $imgPath;
                }
            }
        }

        return $image;
    }
}
