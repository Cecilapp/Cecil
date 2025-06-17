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
    'author' => [
        'name' => 'Cecil',
        'url' => 'https://cecil.app',
        //'email' => '',
    ],
    //'image' => '', // `og:image`
    //'social' => [
    //    'social_network' => [
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
        //        'language' => '<language code>', // optional
        //        'path' => '<path_with_palceholders>',
        //    ]
        //],
        'frontmatter' => 'yaml', // front matter format: `yaml`, `ini`, `toml` or `json`
        'body' => [
            'toc' => ['h2', 'h3'], // headers used to build the table of contents
            'highlight' => false, // enables code syntax highlighting
            'images' => [
                'formats' => [], // creates and adds formats images as `source` (e.g.: ['webp', 'avif'])
                'resize' => 0, // apply a global width to images (in pixels, `0` to disable)
                'responsive' => false, // creates responsive images and adds them to the `srcset` attribute
                'lazy' => true, // adds `loading="lazy"` attribute
                'decoding' => true, // adds `decoding="async"` attribute
                'caption' => false, // puts the image in a <figure> element and adds a <figcaption> containing the title
                'placeholder' => '', // fill <img> background before loading (`color` or `lqip`)
                'class' => '', // puts default CSS class(es) to each image
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
        //'generators' => [ // list of pages generators class, ordered by weight
        //    <position> => 'Cecil\Generator\<class>',
        //],
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
                'default' => '',
                'googlefonts' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.47 Safari/537.36',
                'modern' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
            ],
        ],
        'images' => [
            'optimize' => false, // enables images optimization with JpegOptim, Optipng, Pngquant 2, SVGO 1, Gifsicle, cwebp, avifenc
            'quality' => 75, // image quality after optimization or resize
            'formats' => [], // creates and adds formats images as `source` (e.g.: ['webp', 'avif'])
            'responsive' => [ // options of generated responsive images
                'widths' => [480, 640, 768, 1024, 1366, 1600, 1920], // `srcset` widths
                'sizes' => ['default' => '100vw'] // default `sizes` attribute
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
        'images' => [ // how to handle images in templates
            'formats' => [], // creates and adds formats images as `source` (e.g.: ['webp', 'avif'])
            'responsive' => false, // enables responsive images
            ],
        'translations' => [ // i18n
            'dir' => 'translations', // translations files directory
            'formats' => ['yaml', 'mo'], // translations supported formats
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
        //'formats' => [ // https://cecil.app/documentation/configuration/#formats
        //    [
        //        'name' => '<name>',
        //        'mediatype' => '<media/type>',
        //        'filename' => '<filename>',
        //        'extension' => '<extension>',
        //        'exclude' => ['variable1', 'variable2'],
        //    ],
        //],
        'pagetypeformats' => [ // formats applied by page type
            'page' => ['html'],
            'homepage' => ['html', 'atom'],
            'section' => ['html', 'atom'],
            'vocabulary' => ['html'],
            'term' => ['html', 'atom'],
        ],
        //'postprocessors' => [ // list of output post processors class
        //    '<name>' => 'Cecil\Renderer\PostProcessor\<class>',
        //],
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
