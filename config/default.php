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

// Default configuration
return [
    'title'        => 'Site title',
    'baseline'     => '',
    'baseurl'      => '', // e.g.: https://cecil.app/
    'canonicalurl' => false, // if true then `url()` function prepends URL with `baseurl`
    'description'  => 'Site description.',
    'author'       => [
        'name'  => 'Cecil',
        'url'   => 'https://cecil.app',
        //'email' => '',
    ],
    //'image'        => '', // `og:image`
    //'social'       => [
    //    'social_network' => [
    //        'username' => '',
    //        'url'      => '',
    //    ]
    //],
    'date' => [
        'format'   => 'F j, Y', // @see https://www.php.net/manual/fr/datetime.format.php#refsect1-datetime.format-parameters
        //'timezone' => 'Europe/Paris',
    ],
    'language'  => 'en', // main language code
    //'language'  => [ // advanced language options
    //    'code'   => 'en',
    //    'prefix' => false, // use `true` to apply language code prefix to default language pages path
    //],
    'languages' => [
        [
            'code'   => 'en',
            'name'   => 'English',
            'locale' => 'en_US',
        ],
    ],
    'theme' => [], // no theme(s) by default
    //'taxonomies'   => [ // can be disabled with the special "disabled" value
    //    '<plural>' => '<vocabulary>',
    //],
    'pagination' => [
        'max'  => 5, // number of pages by each paginated pages
        'path' => 'page', // path to paginated pages (e.g.: `/blog/page/2/`)
    ],
    'pages' => [ // Markdown content management
        'dir'     => 'pages', // pages files directory
        'ext'     => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'], // supported files formats, by extension
        'exclude' => ['vendor', 'node_modules'], // directories, paths and files name to exclude (accepts globs, strings and regexes)
        'sortby'  => 'date', // collections sort method
        //'sortby'  => [ // advanced sort options
        //    'variable'   => 'date', // date|updated|title|weight
        //    'desc_title' => false,  // false|true
        //    'reverse'    => false,  // false|true
        //],
        'frontmatter' => [
            'format' => 'yaml', // front matter format: `yaml`, `ini`, `toml` or `json`
        ],
        'body' => [
            'format'    => 'markdown', // page body format (only Markdown is supported)
            'toc'       => ['h2', 'h3'], // headers used to build the table of contents
            'highlight' => [
                'enabled' => false, // enables code syntax highlighting
            ],
            'images' => [
                'lazy' => [
                    'enabled' => true, // adds `loading="lazy"` attribute
                ],
                'decoding' => [
                    'enabled' => true, // adds `decoding="async"` attribute
                ],
                'resize' => [
                    'enabled' => false, // enables image resizing by using the `width` extra attribute
                ],
                'formats' => [], // creates and adds formats images as `source`
                'responsive' => [
                    'enabled' => false, // creates responsive images and adds them to the `srcset` attribute
                ],
                'class' => '', // puts default CSS class to each image
                'caption' => [
                    'enabled' => false, // puts the image in a <figure> element and adds a <figcaption> containing the title
                ],
                'remote' => [
                    'enabled'  => true, // turns remote images into Assets to process them
                    'fallback' => [
                        'enabled' => false, // use a fallback generic image if remote image is not found
                        'path'    => '', // path to the fallback image, stored in assets dir
                    ],
                ],
                'placeholder' => '', // fill <img> background before loading ('color' or 'lqip')
            ],
            'links' => [
                'embed' => [
                    'enabled' => false, // turns links in embedded content if possible
                    'video'   => [
                        'ext' => ['mp4', 'webm'], // supported video file types, extensions
                    ],
                    'audio' => [
                        'ext' => ['mp3'], // supported audio file types, extensions
                    ],
                ],
                'external' => [
                    'blank'      => false, // if true open external link in new tab
                    'noopener'   => true,  // add "noopener" to `rel` attribute
                    'noreferrer' => true,  // add "noreferrer" to `rel` attribute
                    'nofollow'   => true,  // add "nofollow" to `rel` attribute
                ]
            ],
            'excerpt' => [
                'separator' => 'excerpt|break', // string to use as separator
                'capture'   => 'before', // part to capture, `before` or `after` the separator
            ],
        ],
        //'generators' => [ // list of pages generators class, ordered by weight
        //    <position> => 'Cecil\Generator\<class>',
        //],
    ],
    'data' => [ // data files
        'dir'  => 'data', // data files directory
        'ext'  => ['yaml', 'yml', 'json', 'xml', 'csv'], // loaded files by extension
        'load' => true, // enables `site.data` collection
    ],
    'static' => [ // static files
        'dir'     => 'static', // static files directory
        'target'  => '', // subdirectory where files are copied
        'exclude' => ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'], // excluded files by extension or pattern
        'load'    => false, // enables `site.static` collection
    ],
    'assets' => [ // assets: CSS, JS, images, etc.
        'dir'    => 'assets', // assets files directory
        'target' => 'assets', // where processed and remote assets are saved
        'fingerprint' => [
            'enabled' => true, // enables fingerprinting
        ],
        'compile' => [
            'enabled'   => true, // enables Sass files compilation
            'style'     => 'expanded', // compilation style (`expanded` or `compressed`)
            'import'    => ['sass', 'scss', 'node_modules'], // list of imported directories
            'sourcemap' => false, // enables sourcemap in debug mode
            //'variables' => ['<name>' => '<value>'], // list of preset variables
        ],
        'minify' => [
            'enabled' => true, // enables CSS et JS minification
        ],
        'images' => [
            'resize' => [
                'dir' => 'thumbnails', // where resized images are stored
            ],
            'optimize' => [
                'enabled' => false, // enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc
            ],
            'quality'    => 75, // image quality after optimization or resize
            'responsive' => [
                'enabled' => false, // `html` filter: creates responsive images
                'widths'  => [480, 640, 768, 1024, 1366, 1600, 1920], // `srcset` widths
                'sizes'   => ['default' => '100vw'] // default `sizes` attribute
            ],
            'formats' => [], // `html` filter: creates and adds formats images as `source` (ie "webp" and/or "avif")
            'cdn' => [
                'enabled'   => false,  // enables Image CDN
                'canonical' => true,   // is `image_url` must be canonical or not
                'remote'    => true,   // includes remote images
                //'account'   => 'xxxx', // provider account
                // Cloudinary
                //'url'       => 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%',
                // Cloudimage
                //'url'       => 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%',
                // TwicPics
                //'url'       => 'https://%account%.twic.pics/%image_url%?twic=v1/resize=%width%/quality=%quality%/output=%format%',
                // imgix
                //'url'       => 'https://%account%.imgix.net/%image_url%?w=%width%&q=%quality%&fm=%format%'
            ]
        ],
    ],
    'layouts' => [ // layouts and templates
        'dir'      => 'layouts', // Twig templates directory
        'internal' => [
            'dir' => 'resources/layouts', // internal templates directory
        ],
        'translations' => [ // i18n
            'dir'      => 'translations', // translations files directory
            'formats'  => ['yaml', 'mo'], // translations supported formats
            'internal' => [
                'dir' => 'resources/translations', // internal translations directory
            ],
        ],
        'extensions' => [ // list of Twig extensions class
            //'<name>' => 'Cecil\Renderer\Extension\<class>',
        ],
        'components' => [ // components
            'dir' => 'components', // components directory
            'ext' => 'twig', // components files extension
        ],
        //'sections' => [ // override layout name of sections (optional)
        //    '<section>' => '<layout>',
        //]
    ],
    'themes' => [
        'dir' => 'themes', // where themes are stored
    ],
    'metatags' => [
        'robots' => 'index,follow', // SEO robots default directive
    ],
    'output' => [ // output formats and post process
        'dir'      => '_site', // output directory
        //'formats'  => [ // https://cecil.app/documentation/configuration/#formats
        //    [
        //        'name'      => '<name>',
        //        'mediatype' => '<media/type>',
        //        'filename'  => '<filename>',
        //        'extension' => '<extension>',
        //        'exclude'   => ['variable1', 'variable2'],
        //    ],
        //],
        'pagetypeformats' => [ // formats applied by page type
            'page'       => ['html'],
            'homepage'   => ['html', 'atom'],
            'section'    => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom'],
        ],
        //'postprocessors' => [ // list of output post processors class
        //    '<name>' => 'Cecil\Renderer\PostProcessor\<class>',
        //],
    ],
    'cache' => [ // cache management
        'enabled'   => true, // enables cache support
        'dir'       => '.cache', // cache files directory
        'templates' => [
            'enabled' => true, // enables cache for Twig templates
            'dir'     => 'templates', // templates files cache directory
        ],
        'assets' => [
            'dir'    => 'assets', // assets files cache directory
            'remote' => [
                'dir' => 'remote', // sub directory where remote files are saved
            ],
        ],
        'translations' => [
            'enabled' => true, // enables cache for translations dictionary
            'dir'     => 'translations', // translations files cache directory
        ],
    ],
    'optimize' => [ // files optimization
        'enabled' => false, // enables files optimization
        'html'    => [
            'enabled' => true, // enables HTML files optimization
            'ext'     => ['html', 'htm'], // supported files extensions
        ],
        'css' => [
            'enabled' => true, // enables CSS files optimization
            'ext'     => ['css'], // supported files extensions
        ],
        'js' => [
            'enabled' => true, // enables JavaScript files optimization
            'ext'     => ['js'], // supported files extensions
        ],
        'images' => [
            'enabled' => true, // enables images files optimization
            'ext'     => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg', 'avif'], // supported files extensions
        ],
    ],
];
