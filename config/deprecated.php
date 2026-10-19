<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

// Deprecated configuration options that have been renamed, moved or removed:
// '<old.key>' => '<new.key>' (or '' if removed), in dot notation.

return [
    // changes between version 7.x and 8.x
    'frontmatter' => 'pages.frontmatter',
    'body' => 'pages.body',
    'defaultpages' => 'pages.default',
    'virtualpages' => 'pages.default',
    'generators' => 'pages.generators',
    'translations' => 'layouts.translations',
    'extensions' => 'layouts.extensions',
    'postprocess' => 'optimize',
    // changes since version 8.37.0
    'pagination' => 'pages.pagination',
    'paths' => 'pages.paths',
    'pages.frontmatter.format' => 'pages.frontmatter',
    'pages.body.format' => '',
    'pages.body.images.remote.fallback.path' => 'pages.body.images.remote.fallback',
    'pages.body.links.embed.video.ext' => 'pages.body.links.embed.video',
    'pages.body.links.embed.audio.ext' => 'pages.body.links.embed.audio',
    'assets.remote.dir' => '',
    'assets.images.resize.dir' => '',
    'cache.templates.dir' => '',
    'cache.assets.dir' => '',
    // changes since version 9.19.0
    'pages.virtual' => 'pages.default',
];
