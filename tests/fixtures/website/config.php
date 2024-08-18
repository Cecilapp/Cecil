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
                'weight' => -999,
            ],
            [
                'id'      => 'about',
                'enabled' => false,
            ],
            [
                'id'     => 'aligny',
                'name'   => 'The author',
                'url'    => 'https://ligny.fr',
                'weight' => 777,
            ],
            [
                'id'     => '404'
            ],
        ],
    ],
    'pagination' => [
        'enabled' => true,
    ],
    'paths' => [
        [
            'section' => 'Blog',
            'path'    => ':section/:year/:month/:day/:slug',
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
                            'weight' => -999,
                        ],
                        [
                            'id'     => 'menu-fr',
                            'name'   => 'Arnaud (FR)',
                            'url'    => 'https://arnaudligny.fr',
                            'weight' => 777,
                        ],
                        [
                            'id'     => '404 (FR)'
                        ],
                    ],
                ],
                'taxonomies' => [
                    'tags' => 'tag',
                ],
            ],
        ],
    ],
    'theme' => [
        'a-theme',
        //'hyde',
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
    'pages' => [
        'generators' => [
            99  => 'Cecil\Generator\TestError',
            100 => 'Cecil\Generator\TitleReplace',
        ],
        'default'    => [
            'sitemap' => [
                'published' => false,
                'priority'  => 99,
            ],
        ],
        'virtual' => [
            [
                'path'   => '_redirects',
                'output' => 'netlify_redirects',
            ],
            [
                'path'      => 'rss',
                'published' => false,
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
                'formats' => ['avif', 'webp'],
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
    ],
    'output' => [
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
        'postprocessors' => [
            'Test'  => 'Cecil\Renderer\PostProcessor\Test',
            'Error' => 'Cecil\Renderer\PostProcessor\Error',
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
    'assets' => [
        'compile' => [
            'enabled'   => true,
            'style'     => 'expanded',
            'variables' => ['test' => '#FFF'],
        ],
        'minify' => [
            'enabled' => false,
        ],
        'fingerprint' => [
            'enabled' => false,
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
            'formats' => ['avif', 'webp'],
            'caption' => [
                'enabled' => true,
            ],
            'remote' => [
                'enabled'  => true,
                'fallback' => [
                    'enabled' => true,
                    'path'    => 'images/cecil-logo.png',
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
    'layouts' => [
        'extensions' => [
            'Test'       => 'Cecil\Renderer\Extension\Test',
            'Test error' => 'Cecil\Renderer\Extension\TestError',
        ],
    ],
    'cache' => [
        'enabled' => true,
    ],
    'optimize' => [
        'enabled' => true,
    ],
];
