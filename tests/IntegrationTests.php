<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Collection\Page\PrefixSuffix;
use Cecil\Config;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationTests extends \PHPUnit\Framework\TestCase
{
    protected $source;
    protected $config;
    protected $destination;
    /**
     * Set to true to keep the generated files after the test.
     * This is useful for debugging purposes, but should not be used in CI.
     */
    public const DEBUG = false;

    public function setUp(): void
    {
        $this->source = Util::joinFile(__DIR__, 'fixtures/website');
        //$this->config = Util::joinFile($this->source, 'config.php');
        $this->config = Util::joinFile($this->source, 'config.yml');
        $this->destination = $this->source;
    }

    public function tearDown(): void
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile($this->destination, '.cecil'));
            $fs->remove(Util::joinFile($this->destination, '.cache'));
            $fs->remove(Util::joinFile($this->destination, '_site'));
        }
    }

    public function testBuild()
    {
        putenv('CECIL_DEBUG=true');
        putenv('CECIL_TITLE=Cecil (env)');
        putenv('CECIL_DESCRIPTION=Description (env)');
        echo "\n";
        //Builder::create(require($this->config), new PrintLogger())
        Builder::create(Config::loadFile($this->config), new PrintLogger())
            ->setSourceDir($this->source)
            ->setDestinationDir($this->destination)
            ->build([
                'drafts'  => true,
                'dry-run' => false,
            ]);
        self::assertTrue(true);
    }

    /**
     * Prefix separators are configurable.
     *
     * By default, both '-' and '_' are accepted.
     * With custom separators, only the configured characters are treated as prefix separators.
     */
    public function testPrefixSeparatorBehavior()
    {
        // Default behavior: '-' and '_' are accepted.
        self::assertTrue(PrefixSuffix::hasPrefix('1-number-test'));
        self::assertSame('number-test', PrefixSuffix::subPrefix('1-number-test'));
        self::assertSame('number-test', PrefixSuffix::sub('1-number-test'));
        self::assertSame('number-test', PrefixSuffix::sub('1_number-test'));

        self::assertTrue(PrefixSuffix::hasPrefix('1_number-test'));
        self::assertSame('1', PrefixSuffix::getPrefix('1_number-test'));
        self::assertSame('number-test', PrefixSuffix::subPrefix('1_number-test'));

        self::assertTrue(PrefixSuffix::hasPrefix('2017-10-19-post-with-date-prefix'));
        self::assertSame('2017-10-19', PrefixSuffix::getPrefix('2017-10-19-post-with-date-prefix'));
        self::assertSame('post-with-date-prefix', PrefixSuffix::subPrefix('2017-10-19-post-with-date-prefix'));
        self::assertSame('post-with-date-prefix', PrefixSuffix::sub('2017-10-19-post-with-date-prefix'));

        // Custom behavior: keep dash-separated numeric filenames untouched.
        self::assertFalse(PrefixSuffix::hasPrefix('1-number-test', ['_']));
        self::assertSame('1-number-test', PrefixSuffix::subPrefix('1-number-test', ['_']));
        self::assertSame('1-number-test', PrefixSuffix::sub('1-number-test', ['_']));
        self::assertTrue(PrefixSuffix::hasPrefix('1_number-test', ['_']));
        self::assertSame('1', PrefixSuffix::getPrefix('1_number-test', ['_']));
        self::assertSame('number-test', PrefixSuffix::subPrefix('1_number-test', ['_']));
    }
}
