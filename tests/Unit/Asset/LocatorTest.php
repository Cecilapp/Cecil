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

use Cecil\Builder;
use Cecil\Asset\Locator;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LocatorTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private string $destinationDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-locator-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $this->destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';

        $this->filesystem->mkdir([
            $this->sourceDir,
            $this->destinationDir,
        ]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

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

    public function testLocateFindsLocalizedFileInAssetsDirectory(): void
    {
        $this->filesystem->mkdir($this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css');
        file_put_contents($this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'app.fr.css', 'body{}');

        $locator = new Locator($this->createBuilder());
        $result = $locator->locate('css/app.css', language: 'fr');

        self::assertSame('css/app.fr.css', $result['path']);
        self::assertFileExists($result['file']);
    }

    public function testLocateFallsBackToThemeAssetsThenStatic(): void
    {
        $themeName = 'my-theme';
        $themeAssetsDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'assets';
        $staticDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'static';
        $this->filesystem->mkdir([$themeAssetsDir, $staticDir]);

        file_put_contents($themeAssetsDir . DIRECTORY_SEPARATOR . 'theme.css', 'theme');
        file_put_contents($staticDir . DIRECTORY_SEPARATOR . 'fallback.css', 'static');

        $locator = new Locator($this->createBuilder([
            'theme' => $themeName,
        ]));

        $fromTheme = $locator->locate('theme.css');
        self::assertSame('theme.css', $fromTheme['path']);
        self::assertStringContainsString('themes', $fromTheme['file']);

        $fromStatic = $locator->locate('fallback.css');
        self::assertSame('fallback.css', $fromStatic['path']);
        self::assertStringContainsString('static', $fromStatic['file']);
    }

    public function testLocateFallsBackToThemeStaticDirectory(): void
    {
        $themeName = 'my-theme';
        $themeStaticDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $themeName . DIRECTORY_SEPARATOR . 'static';
        $this->filesystem->mkdir($themeStaticDir);
        file_put_contents($themeStaticDir . DIRECTORY_SEPARATOR . 'theme-static.css', 'theme-static');

        $locator = new Locator($this->createBuilder([
            'theme' => $themeName,
        ]));

        $result = $locator->locate('theme-static.css');

        self::assertSame('theme-static.css', $result['path']);
        self::assertStringContainsString('themes', $result['file']);
        self::assertStringContainsString('static', $result['file']);
    }

    public function testLocateThrowsWhenFileCannotBeFound(): void
    {
        $locator = new Locator($this->createBuilder());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to locate file');

        $locator->locate('missing.css');
    }

    public function testLocateRemoteWithFallbackUsesFallbackPath(): void
    {
        $previousTimeout = \ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '1');

        try {
            $this->filesystem->mkdir($this->sourceDir . DIRECTORY_SEPARATOR . 'assets');
            file_put_contents($this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'local.css', 'body{}');

            $locator = new Locator($this->createBuilder());
            $result = $locator->locate('http://127.0.0.1:9/style.css', 'local.css');

            self::assertSame('local.css', $result['path']);
            self::assertFileExists($result['file']);
        } finally {
            if ($previousTimeout !== false) {
                ini_set('default_socket_timeout', (string) $previousTimeout);
            }
        }
    }

    public function testLocateRemoteWithoutFallbackThrowsRuntimeException(): void
    {
        $previousTimeout = \ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '1');

        try {
            $locator = new Locator($this->createBuilder());

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Unable to get remote file');

            $locator->locate('http://127.0.0.1:9/style.css');
        } finally {
            if ($previousTimeout !== false) {
                ini_set('default_socket_timeout', (string) $previousTimeout);
            }
        }
    }

    private function createBuilder(array $config = []): Builder
    {
        $builder = new Builder(array_merge([
            'baseurl' => 'https://example.com/',
        ], $config), new PrintLogger(Builder::VERBOSITY_VERBOSE));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);

        return $builder;
    }
}
