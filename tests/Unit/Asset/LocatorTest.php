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

namespace Cecil\Test\Unit\Asset;

use Cecil\Asset\Locator;
use PHPUnit\Framework\TestCase;

class LocatorTest extends TestCase
{
    // --- buildPathFromUrl ---

    public function testBuildPathFromUrlBasic(): void
    {
        $result = Locator::buildPathFromUrl('https://example.com/style.css');
        self::assertStringContainsString('example.com', $result);
        self::assertStringEndsWith('.css', $result);
    }

    public function testBuildPathFromUrlOnlyDomain(): void
    {
        $result = Locator::buildPathFromUrl('https://example.com/');
        self::assertStringContainsString('example.com', $result);
        self::assertStringContainsString('index', $result);
    }

    public function testBuildPathFromUrlWithQuery(): void
    {
        $result = Locator::buildPathFromUrl('https://fonts.googleapis.com/css2?family=Roboto');
        self::assertStringContainsString('fonts.googleapis.com', $result);
    }

    public function testBuildPathFromUrlGoogleFontsHack(): void
    {
        // Google Fonts CSS URLs end with /css or /css2 — slugified to an extension-free path
        $result = Locator::buildPathFromUrl('https://fonts.googleapis.com/css?family=Roboto');
        self::assertStringEndsWith('.css', $result);
    }

    // --- buildLocalizedPath ---

    public function testBuildLocalizedPathBasic(): void
    {
        $result = Locator::buildLocalizedPath('style.css', 'fr');
        self::assertSame('style.fr.css', $result);
    }

    public function testBuildLocalizedPathWithDirectory(): void
    {
        $result = Locator::buildLocalizedPath('css/style.css', 'en');
        self::assertStringContainsString('style.en.css', $result);
        self::assertStringContainsString('css', $result);
    }

    public function testBuildLocalizedPathNullWhenNoLanguage(): void
    {
        $result = Locator::buildLocalizedPath('style.css', null);
        self::assertNull($result);
    }

    public function testBuildLocalizedPathNullWhenAlreadyLocalized(): void
    {
        $result = Locator::buildLocalizedPath('style.fr.css', 'fr');
        self::assertNull($result);
    }

    public function testBuildLocalizedPathNullWhenNoExtension(): void
    {
        $result = Locator::buildLocalizedPath('noextension', 'fr');
        self::assertNull($result);
    }

    // --- sanitize ---

    public function testSanitizeReplacesReservedChars(): void
    {
        $result = Locator::sanitize('file<name>:test');
        self::assertStringNotContainsString('<', $result);
        self::assertStringNotContainsString('>', $result);
        self::assertStringNotContainsString(':', $result);
    }
}
