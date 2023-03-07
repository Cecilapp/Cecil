<?php

return [
    'debug'      => true,
    'title'      => 'Cecil test',
    'taxonomies' => [
        'tests' => 'disabled',
    ],
    'menus' => [
        'main' => [
            [
                'id'     => 'index',
                'name'   => 'Homepage',
                'weight' => -9999,
            ],
            [
                'id'      => 'about',
                'enabled' => false,
            ],
            [
                'id'     => 'aligny',
                'name'   => 'The author',
                'url'    => 'https://arnaudligny.fr',
                'weight' => 9999,
            ],
        ],
    ],
    'pagination' => [
        'enabled' => true,
    ],
    'theme' => [
        'a-theme',
        //'hyde',
    ],
    'googleanalytics' => 'UA-XXXXX',
    'output'          => [
        'formats' => [
            [
                'name'      => 'netlify_redirects',
                'mediatype' => 'text/plain',
                'extension' => '',
            ],
        ],
        'pagetypeformats' => [
            'page'       => ['html', 'json'],
            'homepage'   => ['html', 'atom', 'rss', 'json'],
            'section'    => ['html', 'atom', 'rss', 'json', 'jsonfeed'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom', 'rss'],
        ],
    ],
    'language'  => 'en',
    'languages' => [
        [
            'code'   => 'en',
            'name'   => 'English',
            'locale' => 'en',
        ],
        [
            'code'   => 'fr',
            'name'   => 'Français',
            'locale' => 'fr_FR',
            'config' => [
                'title'       => 'Cecil FR',
                'description' => 'En français !',
                'menus'       => [
                    'main' => [
                        [
                            'id'     => 'index',
                            'weight' => -9999,
                        ],
                        [
                            'id'     => 'menu-fr',
                            'name'   => 'Arnaud (FR)',
                            'url'    => 'https://arnaudligny.fr',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'virtualpages' => [
        [
            'path'   => '_redirects',
            'output' => 'netlify_redirects',
        ],
        [
            'path'      => 'rss',
            'published' => false,
        ],
    ],
    'defaultpages'    => [
        'sitemap' => [
            'published' => false,
            'priority'  => 99,
        ],
    ],
    'static' => [
        'exclude' => [
            'test*.txt',
            '/\.php$/',
            '*.scss',
            'path',
        ],
        'load' => true,
    ],
    'cache' => [
        'enabled' => true,
    ],
    'assets' => [
        'compile' => [
            'enabled'   => true,
            'style'     => 'expanded',
            'variables' => ['test' => '#FFF'],
        ],
        'minify' => [
            'enabled' => true,
        ],
        'fingerprint' => [
            'enabled' => true,
        ],
        'images' => [
            'optimize' => [
                'enabled' => true,
            ],
            'responsive' => [
                'enabled' => true,
                'sizes'   => [
                    'img' => '100vw',
                ],
            ],
            'webp' => [
                'enabled' => true,
            ],
        ],
    ],
    'postprocess' => [
        'enabled' => true,
    ],
    'paths' => [
        [
            'section' => 'Blog',
            'path'    => ':section/:year/:month/:day/:slug',
        ],
    ],
    'podcast' => [
        'author' => 'Cecil',
        'owner'  => [
            'name'  => 'Cecil',
            'email' => 'contact@cecil.app',
        ],
        'image'      => '/images/cecil-logo.png',
        'categories' => [
            'Society & Culture',
            'History',
        ],
    ],
    'metatags' => [
        'jsonld' => [
            'enabled'  => true,
            'articles' => 'blog',
        ],
    ],
    'body' => [
        'images' => [
            'lazy' => [
                'enabled' => true,
            ],
            'resize' => [
                'enabled' => true,
            ],
            'responsive' => [
                'enabled' => true,
            ],
            'webp' => [
                'enabled' => true,
            ],
            'caption' => [
                'enabled' => true,
            ],
            'remote' => [
                'enabled' => true,
                'fallback' => [
                    'enabled' => true,
                    'path' => 'images/cecil-logo.png',
                ],
            ],
            'class' => 'img',
        ],
        'notes' => [
            'enabled' => true,
        ],
        'highlight' => [
            'enabled' => true,
        ],
    ],
    'generators' => [
        99  => 'Cecil\Generator\Test',
        100 => 'Cecil\Generator\TitleReplace',
    ],
    'extensions' => [
        'Test' => 'Cecil\Renderer\Extension\Test',
    ],
];
