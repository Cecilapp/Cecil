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

// Website default configuration
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
    'image'        => '', // `og:image`
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
    'taxonomies'   => [
        'tags'       => 'tag', // can be disabled with the special "disabled" value
        'categories' => 'category',
    ],
    'pagination' => [
        'max'  => 5, // number of pages by each paginated pages
        'path' => 'page', // path to paginated pages (e.g.: `/blog/page/2/`)
    ],
    // Markdown content management
    'pages' => [
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
                    'enabled'  => true, // turns remote images to Asset to handling them
                    'fallback' => [
                        'enabled' => false, // enables a fallback if image is not found
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
        'generators' => [ // list of pages generators class, ordered by weight
            10 => 'Cecil\Generator\DefaultPages',
            20 => 'Cecil\Generator\VirtualPages',
            30 => 'Cecil\Generator\ExternalBody',
            40 => 'Cecil\Generator\Section',
            50 => 'Cecil\Generator\Taxonomy',
            60 => 'Cecil\Generator\Homepage',
            70 => 'Cecil\Generator\Pagination',
            80 => 'Cecil\Generator\Alias',
            90 => 'Cecil\Generator\Redirect',
        ],
        'default' => [ // default pages
            'index' => [
                'path'      => '',
                'title'     => 'Home',
                'published' => true,
            ],
            '404' => [
                'path'      => '404',
                'title'     => 'Page not found',
                'layout'    => '404',
                'uglyurl'   => true,
                'published' => true,
                'exclude'   => true,
            ],
            'robots' => [
                'path'         => 'robots',
                'title'        => 'Robots.txt',
                'layout'       => 'robots',
                'output'       => 'txt',
                'published'    => true,
                'exclude'      => true,
                'multilingual' => false,
            ],
            'sitemap' => [
                'path'         => 'sitemap',
                'title'        => 'XML sitemap',
                'layout'       => 'sitemap',
                'output'       => 'xml',
                'changefreq'   => 'monthly',
                'priority'     => '0.5',
                'published'    => true,
                'exclude'      => true,
                'multilingual' => false,
            ],
            'xsl/atom' => [
                'path'      => 'xsl/atom',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => true,
                'exclude'   => true,
            ],
            'xsl/rss' => [
                'path'      => 'xsl/rss',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => false,
                'exclude'   => true,
            ],
        ],
    ],
    // data files
    'data' => [
        'dir'  => 'data', // data files directory
        'ext'  => ['yaml', 'yml', 'json', 'xml', 'csv'], // loaded files by extension
        'load' => true, // enables `site.data` collection
    ],
    // static files
    'static' => [
        'dir'     => 'static', // static files directory
        'target'  => '', // subdirectory where files are copied
        'exclude' => ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'], // excluded files by extension or pattern
        'load'    => false, // enables `site.static` collection
    ],
    // assets: CSS, JS, images...
    'assets' => [
        'dir'    => 'assets', // assets files directory
        'target' => 'assets', // where remote and resized assets are saved
        'fingerprint' => [
            'enabled' => true, // enables fingerprinting
        ],
        'compile' => [
            'enabled'   => true, // enables Sass files compilation
            'style'     => 'expanded', // compilation style (`expanded` or `compressed`)
            'import'    => ['sass', 'scss', 'node_modules'], // list of imported paths
            'sourcemap' => false, // enables sourcemap in debug mode
            //'variables' => ['var' => 'value'], // list of preset variables (empty by default)
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
    // layouts and templates
    'layouts' => [
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
            //'Name' => 'Cecil\Renderer\Extension\Class',
        ],
    ],
    // themes
    'themes' => [
        'dir' => 'themes', // where themes are stored
    ],
    // SEO robots default directive
    'metatags' => [
        'robots' => 'index,follow',
    ],
    // output formats and post process
    'output' => [
        'dir'      => '_site', // output directory
        'formats'  => [ // https://cecil.app/documentation/configuration/#formats
            // e.g.: blog/post-1/index.html
            [
                'name'      => 'html',
                'mediatype' => 'text/html',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            // e.g.: blog/atom.xml
            [
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'filename'  => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: blog/rss.xml
            [
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'filename'  => 'rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: blog.json
            [
                'name'      => 'json',
                'mediatype' => 'application/json',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            // e.g.: blog.xml
            [
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            // e.g.: robots.txt
            [
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            // e.g.: blog/post-1/amp/index.html
            [
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'subpath'   => 'amp',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            // e.g.: sw.js
            [
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'extension' => 'js',
            ],
            // e.g.: manifest.webmanifest
            [
                'name'      => 'webmanifest',
                'mediatype' => 'application/manifest+json',
                'extension' => 'webmanifest',
            ],
            // e.g.: atom.xsl
            [
                'name'      => 'xsl',
                'mediatype' => 'application/xml',
                'extension' => 'xsl',
            ],
            // e.g.: blog/feed.json
            [
                'name'      => 'jsonfeed',
                'mediatype' => 'application/json',
                'filename'  => 'feed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: video/embed.html
            [
                'name'      => 'iframe',
                'mediatype' => 'text/html',
                'filename'  => 'embed',
                'extension' => 'html',
                'exclude'   => ['redirect', 'paginated'],
            ],
            // e.g.: video/embed.json
            [
                'name'      => 'oembed',
                'mediatype' => 'application/json+oembed',
                'filename'  => 'embed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
        ],
        'pagetypeformats' => [ // formats applied by page type
            'page'       => ['html'],
            'homepage'   => ['html', 'atom'],
            'section'    => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term'       => ['html', 'atom'],
        ],
        'postprocessors' => [ // list of output post processors class
            'GeneratorMetaTag' => 'Cecil\Renderer\PostProcessor\GeneratorMetaTag',
            'HtmlExcerpt'      => 'Cecil\Renderer\PostProcessor\HtmlExcerpt',
            'MarkdownLink'     => 'Cecil\Renderer\PostProcessor\MarkdownLink',
        ],
    ],
    // cache management
    'cache' => [
        'enabled'   => true, // enables cache support
        'dir'       => '.cache', // cache files directory
        'templates' => [
            'enabled' => true, // enables cache for Twig templates
            'dir'     => 'templates', // templates files cache directory
        ],
        'assets' => [
            'dir'    => 'assets', // assets files cache directory
            'remote' => [
                'dir' => 'remote', // remote files cache directory
            ],
        ],
        'translations' => [
            'enabled' => true, // enables cache for translations dictionary
            'dir'     => 'translations', // translations files cache directory
        ],
    ],
    // files optimization
    'optimize' => [
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
