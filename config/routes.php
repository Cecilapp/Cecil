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
        'name'    => 'init',
        'route'   => '[<path>] [--force|-f]',
        'aliases' => [
            'f' => 'force',
        ],
        'short_description'    => 'Create a new PHPoole website',
        'description'          => 'Build a new PHPoole website in <path> if provided',
        'options_descriptions' => [
            '<path>'     => 'Website path',
            '--force|-f' => 'Override if already exist',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Init',
    ],
    [
        'name'    => 'build',
        'route'   => '[<path>] [--serve|-s] [--watch|-w]',
        'aliases' => [
            's' => 'serve',
            'w' => 'watch',
        ],
        'short_description'    => 'Build website',
        'description'          => 'Build website',
        'options_descriptions' => [
            '<path>'    => 'Website path',
            '--serve|s' => 'Build and serve',
            '--watch|w' => 'Watching files changes',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Build',
    ],
    [
        'name'    => 'serve',
        'route'   => '[<path>] [--watch|-w]',
        'aliases' => [
            'w' => 'watch',
        ],
        'short_description'    => 'Start built-in web server',
        'description'          => 'Start built-in web server',
        'options_descriptions' => [
            '<path>'    => 'Website path',
            '--watch|w' => 'Watching files changes',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\Serve',
    ],
    [
        'name'                 => 'list',
        'route'                => '[<path>]',
        'short_description'    => 'Lists content',
        'description'          => 'Lists content',
        'options_descriptions' => [
            '<path>'  => 'Website path',
        ],
        'defaults' => [
            'path' => getcwd(),
        ],
        'handler' => 'PHPoole\Command\ListContent',
    ],
];
