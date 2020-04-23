<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class Build extends \PHPUnit\Framework\TestCase
{
    protected $wsSourceDir;
    protected $wsDestinationDir;
    const DEBUG = false;

    public function setUp()
    {
        $this->wsSourceDir = Util::joinFile(__DIR__, 'fixtures/website');
        $this->wsDestinationDir = $this->wsSourceDir;
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile($this->wsDestinationDir, '_site'));
            $fs->remove(Util::joinFile($this->wsDestinationDir, '.cache'));
        }
    }

    public function testBuid()
    {
        putenv('CECIL_DEBUG=true');
        putenv('CECIL_TITLE=Cecil');
        putenv('CECIL_DESCRIPTION=Description (env. variable)');
        Builder::create(
            [
                'debug'      => true,
                'title'      => 'Cecil test',
                'taxonomies' => [
                    'tests' => 'disabled',
                ],
                'menus' => [
                    'main' => [
                        [
                            'id'   => 'index',
                            'name' => 'Da home! \o/',
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
                    'hyde',
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
                        'section'    => ['html', 'atom', 'rss', 'json'],
                        'vocabulary' => ['html'],
                        'term'       => ['html', 'atom', 'rss'],
                    ],
                ],
                'language'  => 'en',
                'languages' => [
                    [
                        'code'   => 'en',
                        'name'   => 'English',
                        'locale' => 'en_US',
                    ],
                    [
                        'code'   => 'fr',
                        'name'   => 'Français',
                        'locale' => 'fr_FR',
                        'config' => [
                            'title'       => 'Cecil FR',
                            'description' => 'En français !',
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
                    ],
                    'load' => true,
                ],
                'generators' => [
                    99  => 'Cecil\Generator\Test',
                    100 => 'Cecil\Generator\TitleReplace',
                ],
                'cache' => [
                    'enabled' => true,
                ],
            ], new PrintLogger(Builder::VERBOSITY_DEBUG)
        )->setSourceDir($this->wsSourceDir)
        ->setDestinationDir($this->wsDestinationDir)
        ->build([
            'drafts'  => false,
            'dry-run' => false,
        ]);

        self::assertTrue(true);
    }
}
