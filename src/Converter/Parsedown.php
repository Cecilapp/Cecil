<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Converter;

use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as Image;
use ParsedownExtra;

class Parsedown extends ParsedownExtra
{
    const TMP_DIR = '.cecil';
    const PATTERN = '(.*)(\?|\&)([^=]+)\=([^&]+)'; // https://regex101.com/r/EhIh5N/2
    private $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(Config $config = null)
    {
        parent::__construct();
        $this->config = $config;
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

        preg_match('/'.self::PATTERN.'/s', $image['element']['attributes']['src'], $matches);
        // nothing to do
        if (empty($matches)) {
            return $image;
        }
        $image['element']['attributes']['src'] = $matches[1]; // URL without query string

        // no config or no GD, can't process
        if ($this->config === null || !extension_loaded('gd')) {
            return $image;
        }

        // has resize value
        if (array_key_exists(3, $matches) && $matches[3] == 'resize') {
            $resize = (int) $matches[4];
            $image['element']['attributes']['width'] = $resize;
            // external image? return data URL
            if (preg_match('~^(?:f|ht)tps?://~i', $image['element']['attributes']['src'])) {
                try {
                    $img = Image::make($image['element']['attributes']['src']);
                } catch (NotReadableException $e) {
                    throw new Exception(sprintf('Cannot get image "%s"', $image['element']['attributes']['src']));
                }
                $imgPath = (string) $img->encode('data-url');
                $image['element']['attributes']['src'] = $imgPath;

                return $image;
            }
            // save thumb file
            $img = Image::make($this->config->getStaticPath().'/'.$image['element']['attributes']['src']);
            $img->resize($resize, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $imgThumbPath = '/'.self::TMP_DIR.'/images/thumbs/'.$resize;
            $imgPath = $imgThumbPath.$image['element']['attributes']['src'];
            $imgSubdir = Util::getFS()->makePathRelative(
                dirname($imgPath),
                $imgThumbPath
            );
            Util::getFS()->mkdir($this->config->getDestinationDir().$imgThumbPath.'/'.$imgSubdir);
            $img->save($this->config->getDestinationDir().$imgPath);
            $imgPath = '/images/thumbs/'.$resize.$image['element']['attributes']['src'];
            $image['element']['attributes']['src'] = $imgPath;
        }

        return $image;
    }
}
