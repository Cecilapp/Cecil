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

        $query = parse_url($image['element']['attributes']['src'], PHP_URL_QUERY);
        // nothing to do
        if ($query === null) {
            return $image;
        }

        // Clean URL
        $image['element']['attributes']['src'] = strtok($image['element']['attributes']['src'], '?');

        // external image?
        $external = false;
        if (Util::isExternalUrl($image['element']['attributes']['src'])) {
            $external = true;
        }
        // has resize value
        parse_str($query, $result);
        if (array_key_exists('resize', $result)) {
            $resize = (int) $result['resize'];
            $image['element']['attributes']['width'] = $resize;
            // size is OK: nothing to do
            list($width, $height) = getimagesize(
                $external ?
                $image['element']['attributes']['src']
                : $this->config->getStaticPath().'/'.$image['element']['attributes']['src']
            );
            if ($width <= $resize && $height <= $resize) {
                return $image;
            }
            // no config or no GD, can't process
            if ($this->config === null || !extension_loaded('gd')) {
                return $image;
            }
            // return data URL
            if ($external) {
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
            $img->resize($resize, null, function (\Intervention\Image\Constraint $constraint) {
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
