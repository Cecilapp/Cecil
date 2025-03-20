<?php

declare(strict_types=1);

// This file contains a list of deprecated configuration options that have been renamed or moved.
return [
    // changes between version 7.x and 8.x
    'frontmatter'  => 'pages:frontmatter',
    'body'         => 'pages:body',
    'defaultpages' => 'pages:default',
    'virtualpages' => 'pages:virtual',
    'generators'   => 'pages:generators',
    'translations' => 'layouts:translations',
    'extensions'   => 'layouts:extensions',
    'postprocess'  => 'optimize',
    // changes from version 8.35
    'pagination'   => 'pages:pagination',
];
