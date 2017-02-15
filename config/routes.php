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
        'short_description'    => 'Creates a new website',
        'description'          => 'Creates a new website in current directory, or in <path> if provided.',
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
        'route'   => '[<path>] [--serve|-s] [--watch|-w]',
        'aliases' => [
            's' => 'serve',
            'w' => 'watch',
        ],
        'short_description'    => 'Builds the website',
        'description'          => 'Builds the website.',
        'options_descriptions' => [
            '<path>'     => 'Website path',
            '--serve|-s' => 'Builds and serves',
            '--watch|-w' => 'Watching files changes',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Build',
    ],
    [
        'name'    => 'serve',
        'route'   => '[<path>] [--watch|-w] [--browser|-b]',
        'aliases' => [
            'w' => 'watch',
            'b' => 'browser',
        ],
        'short_description'    => 'Starts the built-in Web server',
        'description'          => 'Starts the built-in Web server.',
        'options_descriptions' => [
            '<path>'       => 'Website path',
            '--watch|-w'   => 'Watching files changes',
            '--browser|-b' => 'Open browser on serve',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Serve',
    ],
    [
        'name'                 => 'list',
        'route'                => '[<path>]',
        'short_description'    => 'Lists website content',
        'description'          => 'Lists website content files.',
        'options_descriptions' => [
            '<path>'  => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\ListContent',
    ],
];
