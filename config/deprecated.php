<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file contains a list of deprecated configuration options that have been renamed or moved.

return [
    // changes between version 7.x and 8.x
    'frontmatter' => 'pages:frontmatter',
    'body' => 'pages:body',
    'defaultpages' => 'pages:default',
    'virtualpages' => 'pages:virtual',
    'generators' => 'pages:generators',
    'translations' => 'layouts:translations',
    'extensions' => 'layouts:extensions',
    'postprocess' => 'optimize',
    // changes since version 8.37.0
    'pagination' => 'pages:pagination',
    'paths' => 'pages:paths',
    'pages.frontmatter.format' => 'pages.frontmatter',
    'pages.body.format' => '',
    'pages.body.images.remote.fallback.path' => 'pages.body.images.remote.fallback',
    'pages.body.links.embed.video.ext' => 'pages.body.links.embed.video',
    'pages.body.links.embed.audio.ext' => 'pages.body.links.embed.audio',
    'assets.remote.dir' => '',
    'assets.images.resize.dir' => '',
    'cache.templates.dir' => '',
    'cache.assets.dir' => '',
];
