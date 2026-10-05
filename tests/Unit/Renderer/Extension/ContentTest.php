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

namespace Cecil\Test\Unit\Renderer\Extension;

use Cecil\Builder;
use Cecil\Renderer\Extension\Content;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    private function createExtension(): Content
    {
        return new Content(new Builder(['baseurl' => 'https://example.com/']));
    }

    public function testReadtimeReturnsOneMinuteMinimum(): void
    {
        $extension = $this->createExtension();

        self::assertSame('1', $extension->readtime(null));
        self::assertSame('1', $extension->readtime(''));
        self::assertSame('1', $extension->readtime('<p>Less than two hundred words.</p>'));
    }

    public function testReadtimeCountsTwoHundredWordsPerMinute(): void
    {
        $extension = $this->createExtension();

        self::assertSame('1', $extension->readtime(implode(' ', array_fill(0, 200, 'word'))));
        self::assertSame('2', $extension->readtime(implode(' ', array_fill(0, 400, 'word'))));
        self::assertSame('3', $extension->readtime(implode(' ', array_fill(0, 700, 'word'))));
    }

    public function testReadtimeIgnoresMarkup(): void
    {
        $extension = $this->createExtension();

        $text = str_repeat("<p><strong>word</strong> word</p>\n", 200); // 400 words

        self::assertSame('2', $extension->readtime($text));
    }
}
