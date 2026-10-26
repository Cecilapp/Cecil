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

namespace Cecil\Test\Unit\Converter;

use Cecil\Builder;
use Cecil\Converter\Converter;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class CustomConverterTest extends TestCase
{
    public function testDefaultConverter(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/'], new NullLogger());

        self::assertInstanceOf(Converter::class, $builder->getConverter());
        self::assertSame($builder->getConverter(), $builder->getConverter());
    }

    public function testInvalidConverterIsRejected(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/', 'pages' => ['converter' => \stdClass::class]], new NullLogger());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('`pages.converter`');

        $builder->getConverter();
    }

    public function testCustomConverterIsUsedToBuildPages(): void
    {
        $filesystem = new Filesystem();
        $sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-converter-' . uniqid('', true));
        $filesystem->dumpFile(Util::joinFile($sourceDir, 'pages', 'test.md'), "---\ntitle: Test\n---\nBody");

        try {
            $builder = (new Builder([
                'baseurl' => 'https://example.com/',
                'cache'   => false,
                'pages'   => ['converter' => UppercaseConverter::class],
            ], new NullLogger()))->setSourceDir($sourceDir);
            $builder->build(['dry-run' => true]);

            self::assertSame('<p>BODY</p>', $builder->getPages()->get('test')->getBodyHtml());
        } finally {
            $filesystem->remove($sourceDir);
        }
    }
}

/**
 * Converts the body to uppercase, using the default converter.
 */
class UppercaseConverter extends Converter
{
    public function convertBody(string $string, ?string $language = null): string
    {
        return parent::convertBody(strtoupper($string), $language);
    }
}
