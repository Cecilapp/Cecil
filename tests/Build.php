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
        putenv('CECIL_SITE_TITLE=Cecil test (env)');
        Builder::create(
            [
                'site' => [
                    'title' => 'Cecil test',
                    'menu'  => [
                        'main' => [
                            'index' => [
                                'id'     => 'index',
                                'name'   => 'Da home!',
                                'url'    => '',
                                'weight' => 1,
                            ],
                            'about' => [
                                'id'       => 'about',
                                'disabled' => true,
                            ],
                            'narno' => [
                                'id'     => 'narno',
                                'name'   => 'narno.org',
                                'url'    => 'http://narno.org',
                                'weight' => 999,
                            ],
                        ],
                    ],
                    'paginate' => [
                        'disabled' => false,
                        'homepage' => [
                            'section' => 'blog',
                        ],
                    ],
                    'taxonomies' => [
                        'disabled' => false,
                    ],
                    'googleanalytics'       => 'POUET',
                    'virtualpages'          => [
                        'sitemap' => [
                            'published' => true,
                        ],
                        'rss' => 'disabled',
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
