<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// CLI routes
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
        'handler' => 'Cecil\Command\NewWebsite',
    ],
    [
        'name'    => 'newpage',
        'route'   => '<name> [<path>] [--force|-f]',
        'aliases' => [
            'f' => 'force',
        ],
        'short_description'    => 'Create a new page',
        'description'          => 'Create a new page "<name>.md".',
        'options_descriptions' => [
            '<name>'     => 'Page name.',
            '<path>'     => 'Website path.',
            '--force|-f' => 'Override if already exist.',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'Cecil\Command\NewPage',
    ],
    [
        'name'    => 'build',
        'route'   => '[<path>] [--drafts|-d] [--baseurl=] [--verbose|-v] [--quiet|-q] [--dry-run]',
        'aliases' => [
            'd' => 'drafts',
            'v' => 'verbose',
            'q' => 'quiet',
        ],
        'short_description'    => 'Build the website',
        'description'          => 'Build the website.',
        'options_descriptions' => [
            '<path>'       => 'Website path',
            '--drafts|-d'  => 'Include drafts',
            '--baseurl'    => 'Base URL',
            '--verbose|-v' => 'Print build details',
            '--quiet|-q'   => 'Not verbose messages',
            '--dry-run'    => 'Build without saving',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'Cecil\Command\Build',
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
        'handler' => 'Cecil\Command\Serve',
    ],
    [
        'name'                 => 'list',
        'route'                => '[<path>]',
        'short_description'    => 'List content pages',
        'description'          => 'List content pages files.',
        'options_descriptions' => [
            '<path>' => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'Cecil\Command\ListContent',
    ],
    [
        'name'                 => 'config',
        'route'                => '[<path>]',
        'short_description'    => 'Display configuration',
        'description'          => 'Display website configuration.',
        'options_descriptions' => [
            '<path>' => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'Cecil\Command\Config',
    ],
    [
        'name'                 => 'clean',
        'route'                => '[<path>]',
        'short_description'    => 'Clean build',
        'description'          => 'Clean build and temporary server files.',
        'options_descriptions' => [
            '<path>' => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'Cecil\Command\Clean',
    ],
];
