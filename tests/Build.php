<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Symfony\Component\Filesystem\Filesystem;

class Build extends \PHPUnit\Framework\TestCase
{
    protected $wsSourceDir;
    protected $wsDestinationDir;
    const DEBUG = false;

    public function setUp()
    {
        $this->wsSourceDir = __DIR__.'/fixtures/website';
        $this->wsDestinationDir = $this->wsSourceDir;
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove($this->wsDestinationDir.'/_site');
            $fs->remove(__DIR__.'/../_cache');
        }
    }

    public function testBuid()
    {
        putenv('CECIL_DESCRIPTION=Description from environment variable');
        Builder::create(
            [
                'title'     => 'Cecil test',
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
                'menus'   => [
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
                    'enabled'  => true,
                ],
                'taxonomies' => [
                    'tests' => 'disabled',
                ],
                'googleanalytics' => 'UA-XXXXX',
                'defaultpages'    => [
                    'sitemap' => [
                        'published' => false,
                        'priority'  => 99,
                    ],
                ],
                'virtualpages'    => [
                    [
                        'path'   => '_redirects',
                        'output' => 'netlify_redirects',
                    ],
                    [
                        'path'      => 'rss',
                        'published' => false,
                    ],
                ],
                'output' => [
                    'formats' => [
                        'netlify_redirects' => [
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
                'theme'  => [
                    'a-theme',
                    'hyde',
                ],
                'static' => [
                    'exclude' => [
                        'test.txt',
                    ],
                ],
                'generators' => [
                    99  => 'Cecil\Generator\Test',
                    //100 => 'Cecil\Generator\TitleReplace',
                ],
                'debug' => true,
            ]
        )->setSourceDir($this->wsSourceDir)
        ->setDestinationDir($this->wsDestinationDir)
        ->build([
            'verbosity' => Builder::VERBOSITY_DEBUG,
            'drafts'    => false,
            'dry-run'   => false,
        ]);

        self::assertTrue(true);
    }
}
