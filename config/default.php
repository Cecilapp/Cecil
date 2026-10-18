<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

// Default configuration
return [
    'title' => 'Site title',
    'baseline' => '',
    'baseurl' => '', // e.g.: https://cecil.app/
    'canonicalurl' => false, // if true then `url()` function prepends URL with `baseurl`
    'description' => 'Site description.',
    //'author' => [
    //    'name' => 'Cecil',
    //    'url' => 'https://cecil.app',
    //    'email' => 'contact@cecil.app',
    //],
    //'image' => '', // `og:image`
    //'social' => [
    //    'network' => [
    //        'username' => '',
    //        'url' => '',
    //    ]
    //],
    //'menus' => [ // site menus
    //    '<main>' => [
    //        [
    //            'id' => '<unique-id>',
    //            'name' => '<name>',
    //            'url' => '<url>',
    //            'weight' => 1,
    //        ],
    //    ],
    //],
    //'taxonomies' => [ // available vocabularies
    //    '<plural>' => '<vocabulary>',
    //    '<plural>' => 'disabled', // can be disabled with the special "disabled" value
    //],
    'theme' => [], // no theme(s) by default
    'date' => [
        'format' => 'F j, Y', // @see https://www.php.net/manual/fr/datetime.format.php#refsect1-datetime.format-parameters
        //'timezone' => 'Europe/Paris',
    ],
    'language' => 'en', // main language code
    //'language' => [ // advanced language options
    //    'code' => 'en',
    //    'prefix' => false, // use `true` to apply language code prefix to default language pages path
    //],
    'languages' => [
        [
            'code' => 'en',
            'name' => 'English',
            'locale' => 'en_EN',
            'enabled' => true,
        ],
    ],
    'metatags' => [
        'robots' => 'index,follow', // SEO robots default directive
    ],
    'pages' => [ // Markdown content management
        'dir' => 'pages', // pages files directory
        'ext' => ['md', 'markdown', 'mdown', 'mkdn', 'mkd', 'text', 'txt'], // supported files formats, by extension
        'prefix' => [
            'separator' => ['-', '_'], // characters used as separator between prefix (date or weight) and slug
        ],
        'exclude' => ['vendor', 'node_modules'], // directories, paths and files name to exclude (accepts globs, strings and regexes)
        'sortby' => 'date', // collections sort method
        //'sortby' => [ // advanced sort options
        //    'variable' => 'date', // date|updated|title|weight
        //    'desc_title' => false,  // false|true
        //    'reverse' => false,  // false|true
        //],
        'pagination' => [
            'max' => 5, // number of pages by each paginated pages
            'path' => 'page', // path to paginated pages (e.g.: `/blog/page/2/`)
        ],
        //'paths' => [
        //    [
        //        'section' => '<section’s ID>',
        //        'path' => '<path_with_placeholders>',
        //    ]
        //],
        'frontmatter' => 'yaml', // front matter format: `yaml`, `ini`, `toml` or `json`
        'body' => [
            'toc' => ['h2', 'h3'], // headers used to build the table of contents
            'highlight' => true, // enables code syntax highlighting
            'images' => [
                'formats' => [], // creates and adds formats images as `source` (e.g.: ['avif', 'webp'])
                'resize' => 0, // apply a global width to images (in pixels, `0` to disable)
                'responsive' => false, // creates responsive images and adds them to the `srcset` attribute
                'lazy' => true, // adds `loading="lazy"` attribute
                'decoding' => true, // adds `decoding="async"` attribute
                'caption' => false, // puts the image in a <figure> element and adds a <figcaption> containing the title
                'placeholder' => '', // fill <img> background before loading (`color` or `lqip`)
                'class' => '', // puts default CSS class(es) to each image
                'dark_suffix' => null, // suffix of the dark variant image file (e.g.: `.dark`), null to disable
                'mobile_suffix' => null, // suffix of the mobile variant image file (e.g.: `.mobile` or `mobile`), null to disable
                'mobile_media_query' => '(max-width: 767px)', // media query used by mobile image `<source>`
                'remote' => [ // turns remote images into Assets to process them (disable with `false`)
                    'fallback' => '', // path to the fallback image, stored in assets directory (empty by default)
                ],
            ],
            'links' => [
                'embed' => [ // turns links in embedded content if possible
                    'enabled' => false,
                    'video' => ['mp4', 'webm'], // supported video file types, by extension
                    'audio' => ['mp3', 'ogg', 'wav'], // supported audio file types, by extension
                ],
                'external' => [
                    'blank' => false, // if `true` open external link in new tab
                    'noopener' => true, // if `true` add "noopener" to `rel` attribute
                    'noreferrer' => true, // if `true` add "noreferrer" to `rel` attribute
                    'nofollow' => false, // if `true` add "nofollow" to `rel` attribute
                ]
            ],
            'excerpt' => [
                'separator' => 'excerpt|break', // string to use as separator
                'capture' => 'before', // part to capture, `before` or `after` the separator
            ],
        ],
        // list of pages generators class, ordered by weight (e.g.: `<position> => 'Cecil\Generator\<class>'`)
        'generators' => [
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
        'default' => [ // default pages, merged by ID
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
                'excluded'  => true,
            ],
            'robots' => [
                'path'         => 'robots',
                'title'        => 'Robots.txt',
                'layout'       => 'robots',
                'output'       => 'txt',
                'published'    => true,
                'excluded'     => true,
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
                'excluded'     => true,
                'multilingual' => false,
            ],
            'xsl/sitemap' => [
                'path'      => 'xsl/sitemap',
                'layout'    => 'sitemap',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => true,
                'excluded'  => true,
            ],
            'xsl/atom' => [
                'path'      => 'xsl/atom',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => true,
                'excluded'  => true,
            ],
            'xsl/rss' => [
                'path'      => 'xsl/rss',
                'layout'    => 'feed',
                'output'    => 'xsl',
                'uglyurl'   => true,
                'published' => false,
                'excluded'  => true,
            ],
        ],
    ],
    'data' => [ // data files
        'dir' => 'data', // data files directory
        'ext' => ['yaml', 'yml', 'json', 'xml', 'csv'], // loaded files by extension
        'load' => true, // enables `site.data` collection
    ],
    'assets' => [ // assets: CSS, JS, images, etc.
        'dir' => 'assets', // assets files directory
        'target' => '', // where processed assets are saved (in output directory)
        'fingerprint' => true, // enables fingerprinting
        'compile' => [ // Sass files compilation
            'style' => 'expanded', // compilation style (`expanded` or `compressed`)
            'import' => ['sass', 'scss', 'node_modules'], // list of imported directories
            'sourcemap' => false, // enables sourcemap in debug mode
            //'variables' => ['<name>' => '<value>'], // list of preset variables
        ],
        'minify' => true, // enables CSS et JS minification
        'remote' => [
            'useragent' => [
                'default' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
                'googlefonts' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36',
                'modern' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
            ],
            'timeout' => 30, // HTTP timeout in seconds for remote asset downloads
        ],
        'images' => [
            'optimize' => false, // enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc
            'quality' => 75, // image quality after optimization or resize
            'responsive' => [ // options of generated responsive images
                'widths' => [480, 640, 768, 1024, 1366, 1600, 1920], // `srcset` widths
                'sizes' => ['default' => '100vw'], // default `sizes` attribute
                'densities' => [1, 2, 3], // `srcset` pixel densities
            ],
            'cdn' => false, // enables Image CDN
            //[
            //    'url' => '', // provider URL, support placeholders: `%account%`, `%width%`, `%quality%`, `%format%`, `%image_url%`
            //    'svg' => false, // is CDN support SVG images
            //    'remote' => true, // includes remote images
            //    'account' => '', // value of %account% placeholder
            //    'canonical' => true, // is `%image_url%` must be canonical or not
            //]
        ],
    ],
    'static' => [ // static files
        'dir' => 'static', // static files directory
        'target' => '', // subdirectory where files are copied
        'exclude' => ['sass', 'scss', '*.scss', 'package*.json', 'node_modules'], // excluded files by extension or pattern
        'load' => false, // enables `site.static` collection
    ],
    'layouts' => [ // layouts and templates
        'dir' => 'layouts', // Twig templates directory
        'autoescape' => false, // Twig autoescape option override (null to use extension-based strategy)
        'images' => [ // how to handle images in templates
            'formats' => [], // used by `html` function: adds alternatives image formats as `source` (e.g.: ['avif', 'webp'])
            'responsive' => false, // used by `html` function: adds responsive images ('width' or 'density') to `srcset` attribute
            'placeholder' => '', // used by `html` function: fills <img> background before loading (`color` or `lqip`)
            'dark_suffix' => null, // suffix of the dark variant image file (e.g.: `.dark`), null to disable
            'mobile_suffix' => null, // suffix of the mobile variant image file (e.g.: `.mobile` or `mobile`), null to disable
            'mobile_media_query' => '(max-width: 767px)', // media query used by mobile image `<source>`
        ],
        'translations' => [ // i18n
            'dir' => 'translations', // translations files directory
            'formats' => [ // translations supported formats
                'yaml' => [
                    'loader' => 'Symfony\Component\Translation\Loader\YamlFileLoader',
                    'ext' => ['yml', 'yaml'],
                ],
                'mo' => [
                    'loader' => 'Symfony\Component\Translation\Loader\MoFileLoader',
                    'ext' => ['mo'],
                ],
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
    'output' => [ // output formats and post process
        'dir' => '_site', // output directory
        // output formats, merged by name (e.g.: `['name' => '<name>', 'mediatype' => '<media/type>', 'filename' => '<filename>', 'extension' => '<extension>', 'exclude' => ['<variable>']]`)
        'formats' => [
            [ // e.g.: blog/post-1/index.html
                'name'      => 'html',
                'mediatype' => 'text/html',
                'rel'       => 'canonical',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            [ // e.g.: blog/atom.xml
                'name'      => 'atom',
                'mediatype' => 'application/atom+xml',
                'rel'       => 'alternate',
                'filename'  => 'atom',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: blog/rss.xml
                'name'      => 'rss',
                'mediatype' => 'application/rss+xml',
                'rel'       => 'alternate',
                'filename'  => 'rss',
                'extension' => 'xml',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: blog.json
                'name'      => 'json',
                'mediatype' => 'application/json',
                'rel'       => 'alternate',
                'extension' => 'json',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: blog.xml
                'name'      => 'xml',
                'mediatype' => 'application/xml',
                'rel'       => 'alternate',
                'extension' => 'xml',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: robots.txt
                'name'      => 'txt',
                'mediatype' => 'text/plain',
                'rel'       => 'alternate',
                'extension' => 'txt',
                'exclude'   => ['redirect'],
            ],
            [ // e.g.: blog/post-1/amp/index.html
                'name'      => 'amp',
                'mediatype' => 'text/html',
                'rel'       => 'alternate',
                'subpath'   => 'amp',
                'filename'  => 'index',
                'extension' => 'html',
            ],
            [ // e.g.: sw.js
                'name'      => 'js',
                'mediatype' => 'application/javascript',
                'rel'       => 'alternate',
                'extension' => 'js',
            ],
            [ // e.g.: manifest.webmanifest
                'name'      => 'webmanifest',
                'mediatype' => 'application/manifest+json',
                'rel'       => 'alternate',
                'extension' => 'webmanifest',
            ],
            [ // e.g.: atom.xsl
                'name'      => 'xsl',
                'mediatype' => 'application/xml',
                'rel'       => 'alternate',
                'extension' => 'xsl',
            ],
            [ // e.g.: blog/feed.json
                'name'      => 'jsonfeed',
                'mediatype' => 'application/json',
                'rel'       => 'alternate',
                'filename'  => 'feed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: video/oembed.json
                'name'      => 'oembed',
                'mediatype' => 'application/json+oembed',
                'rel'       => 'alternate',
                'filename'  => 'oembed',
                'extension' => 'json',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: video/embed.html
                'name'      => 'embed',
                'mediatype' => 'text/html',
                'rel'       => 'alternate',
                'filename'  => 'embed',
                'extension' => 'html',
                'exclude'   => ['redirect', 'paginated'],
            ],
            [ // e.g.: page.md
                'name'      => 'markdown',
                'mediatype' => 'text/markdown',
                'rel'       => 'alternate',
                'extension' => 'md',
            ],
            [ // e.g.: llms.txt
                'name'      => 'llms',
                'mediatype' => 'text/markdown',
                'rel'       => 'describedby',
                'filename'  => 'llms',
                'extension' => 'txt',
            ]
        ],
        'postprocessors' => [ // list of output post processors class
            'GeneratorMetaTag' => 'Cecil\Renderer\PostProcessor\GeneratorMetaTag',
            'HtmlExcerpt'      => 'Cecil\Renderer\PostProcessor\HtmlExcerpt',
            'MarkdownLink'     => 'Cecil\Renderer\PostProcessor\MarkdownLink',
        ],
        'pagetypeformats' => [ // formats applied by page type
            'page' => ['html'],
            'homepage' => ['html', 'atom'],
            'section' => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term' => ['html', 'atom'],
        ],
    ],
    'cache' => [ // cache management
        'enabled' => true, // disable with `false`
        'dir' => '.cache', // cache files root directory
        'assets' => [ // assets cache
            'ttl' => null, // assets cache TTL (no expiration by default)
            'remote' => [
                'ttl' => 604800, // remote assets cache TTL (7 days by default)
            ]
        ],
        'templates' => true, // disable Twig templates cache with `false`
        'translations' => true, // disable translations dictionary cache with `false`
    ],
    'optimize' => [ // files optimization
        'enabled' => false, // enables files optimization
        'html' => [
            'enabled' => true, // enables HTML files optimization
            'ext' => ['html', 'htm'], // supported files extensions
        ],
        'css' => [
            'enabled' => true, // enables CSS files optimization
            'ext' => ['css'], // supported files extensions
        ],
        'js' => [
            'enabled' => true, // enables JavaScript files optimization
            'ext' => ['js'], // supported files extensions
        ],
        'images' => [
            'enabled' => true, // enables images files optimization
            'ext' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'svg', 'avif'], // supported files extensions
        ],
    ],
];
