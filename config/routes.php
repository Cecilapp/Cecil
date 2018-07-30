<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    [
        'name'    => 'new',
        'route'   => '[<path>] [--force|-f]',
        'aliases' => [
            'f' => 'force',
        ],
        'short_description'    => 'Create a new website',
        'description'          => 'Create a new website in current directory, or in <path> if provided.',
        'options_descriptions' => [
            '<path>'     => 'Website path.',
            '--force|-f' => 'Override if already exist.',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\NewWebsite',
    ],
    [
        'name'    => 'build',
        'route'   => '[<path>] [--drafts|-d] [--baseurl=]',
        'aliases' => [
            'd' => 'drafts',
        ],
        'short_description'    => 'Build the website',
        'description'          => 'Build the website.',
        'options_descriptions' => [
            '<path>'      => 'Website path',
            '--drafts|-d' => 'Include drafts',
            '--baseurl'   => 'Base URL',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Build',
    ],
    [
        'name'    => 'serve',
        'route'   => '[<path>] [--drafts|-d] [--open|-o]',
        'aliases' => [
            'd' => 'drafts',
            'o' => 'open',
        ],
        'short_description'    => 'Start the built-in web server',
        'description'          => 'Start the live-reloading-built-in web server.',
        'options_descriptions' => [
            '<path>'      => 'Website path',
            '--drafts|-d' => 'Include drafts',
            '--open|-o'   => 'Open browser automatically',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Serve',
    ],
    [
        'name'                 => 'list',
        'route'                => '[<path>]',
        'short_description'    => 'List website pages',
        'description'          => 'List website pages files.',
        'options_descriptions' => [
            '<path>' => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\ListContent',
    ],
];
