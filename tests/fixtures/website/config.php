<?php

return [
    'title' => 'Cecil test',
    'baseurl' => 'https://cecil.app/',
    'taxonomies' => [
        'tags' => 'tag',
        'categories' => 'category',
        'tests' => 'disabled',
    ],
    'menus' => [
        'main' => [
            [
                'id' => 'index',
                'name' => 'Homepage',
                'weight' => -999,
            ],
            [
                'id' => 'about',
                'enabled' => false,
            ],
            [
                'id' => 'aligny',
                'name' => 'The author',
                'url' => 'https://ligny.fr',
                'weight' => 777,
            ],
            [
                'id' => '404',
                'weight' => 999,
            ],
        ],
    ],
    'language' => 'en',
    'languages' => [
        [
            'code' => 'en',
            'name' => 'English',
            'locale' => 'en',
        ],
        [
            'code' => 'fr',
            'name' => 'Français',
            'locale' => 'fr_FR',
            'config' => [
                'title' => 'Cecil FR',
                'description' => 'En français !',
                'menus' => [
                    'main' => [
                        [
                            'id' => 'index',
                            'weight' => -999,
                        ],
                        [
                            'id' => 'menu-fr',
                            'name' => 'Arnaud (FR)',
                            'url' => 'https://arnaudligny.fr',
                            'weight' => 777,
                        ],
                        [
                            'id' => '404 (FR)',
                            'weight' => 999,
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
        'owner' => [
            'name' => 'Cecil',
            'email' => 'contact@cecil.app',
        ],
        'image' => '/images/cecil-logo.png',
        'categories' => [
            'Society & Culture',
            'History',
        ],
    ],
    'metatags' => [
        'jsonld' => [
            'enabled' => true,
            'articles' => 'blog',
        ],
    ],
    'pages' => [
        'pagination' => true,
        'paths' => [
            [
                'section' => 'Blog',
                'path' => ':section/:year/:month/:day/:slug',
            ],
        ],
        'generators' => [
            99 => 'Cecil\Generator\TestError',
            100 => 'Cecil\Generator\TitleReplace',
        ],
        'default' => [
            'sitemap' => [
                'published' => false,
                'priority' => 99,
            ],
        ],
        'virtual' => [
            [
                'path' => '_redirects',
                'output' => 'netlify_redirects',
            ],
            [
                'path' => 'rss',
                'published' => false,
            ],
        ],
        'body' => [
            'images' => [
                'lazy' => true,
                'resize' => true,
                'responsive' => true,
                'formats' => ['avif', 'webp'],
                'caption' => true,
                'remote' => [
                    'fallback' => 'images/cecil-logo.png',
                ],
                'class' => 'class_img',
                'placeholder' => 'color',
            ],
            'notes' => true,
            'highlight' => true,
            'links' => [
                'embed' => true,
                'external' => [
                    'blank' => true,
                    'nofollow' => true,
                    'noopener' => true,
                    'noreferrer' => true,
                ]
            ],
        ],
    ],
    'output' => [
        'formats' => [
            [
                'name' => 'netlify_redirects',
                'mediatype' => 'text/plain',
                'extension' => '',
            ],
        ],
        'pagetypeformats' => [
            'page' => ['html', 'json'],
            'homepage' => ['html', 'atom', 'rss', 'json'],
            'section' => ['html', 'atom', 'rss', 'json', 'jsonfeed'],
            'vocabulary' => ['html'],
            'term' => ['html', 'atom', 'rss'],
        ],
        'postprocessors' => [
            'Test' => 'Cecil\Renderer\PostProcessor\Test',
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
        'mounts' => [
            'ISS.jpg' => 'mount_test/iss.jpg', // file
            'video' => 'mount_test', // directory
        ],
    ],
    'assets' => [
        'compile' => [
            'style' => 'expanded',
            'variables' => ['test' => '#FFF'],
        ],
        'minify' => false,
        'fingerprint' => false,
        'notes' => true,
        'highlight' => true,
        'images' => [
            'optimize' => true,
            'responsive' => [
                'sizes' => [
                    'class_img' => '100vw',
                ],
            ],
            'formats' => ['avif', 'webp'],
            'caption' => true,
            'remote' => [
                'enabled' => true,
                'fallback' => [
                    'enabled' => true,
                    'path' => 'images/cecil-logo.png',
                ],
            ],
            'class_img' => 'img',
        ],
    ],
    'layouts' => [
        'extensions' => [
            'Test' => 'Cecil\Renderer\Extension\Test',
            'Test error' => 'Cecil\Renderer\Extension\TestError',
        ],
    ],
    'cache' => true,
    'optimize' => true,
];
