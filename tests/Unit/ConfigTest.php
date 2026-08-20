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

namespace Cecil\Test\Unit;

use Cecil\Config;
use Cecil\Exception\ConfigException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ConfigTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-config-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testEmptyCacheDirectoryIsRejectedWhenCacheIsEnabled(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('The cache directory (`cache.dir`) must not be empty when cache is enabled.');

        new Config([
            'cache' => [
                'dir' => '',
            ],
        ]);
    }

    public function testInvalidOutputFormatsStructureIsRejected(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Output format #16 must be an array.');

        new Config([
            'output' => [
                'formats' => ['html'],
            ],
        ]);
    }

    public function testLoadFileThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('does not exist');

        Config::loadFile($this->tmpDir . DIRECTORY_SEPARATOR . 'missing.yml');
    }

    public function testLoadFileReturnsEmptyArrayWhenIgnoredAndMissing(): void
    {
        self::assertSame([], Config::loadFile($this->tmpDir . DIRECTORY_SEPARATOR . 'missing.yml', true));
    }

    public function testLoadFileThrowsOnInvalidYaml(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'invalid.yml';
        file_put_contents($file, "title: [unterminated\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('parsing error');

        Config::loadFile($file);
    }

    public function testGetCachePathThrowsWhenCacheDirectoryIsUndefined(): void
    {
        $config = new Config([
            'cache' => [
                'enabled' => false,
                'dir' => '',
            ],
        ]);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('cache directory (`cache.dir`) is not defined');

        $config->getCachePath();
    }

    public function testGetThemeReturnsArrayWhenConfiguredAsString(): void
    {
        $config = new Config();
        $config->import([
            'theme' => 'cecil-theme',
        ]);

        self::assertSame(['cecil-theme'], $config->getTheme());
    }

    public function testGetAndHasUseLanguageSpecificConfiguration(): void
    {
        $config = new Config();
        $config->import([
            'language' => 'en',
            'title' => 'Global title',
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US', 'config' => ['title' => 'English title']],
                ['code' => 'fr', 'locale' => 'fr_FR', 'config' => ['title' => 'Titre francais']],
            ],
        ]);

        self::assertTrue($config->has('title', 'fr'));
        self::assertSame('Titre francais', $config->get('title', 'fr'));
    }

    public function testGetReturnsDefaultConfigurationValueWhenFallbackIsDisabledAndNoLocalizedValue(): void
    {
        $config = new Config();
        $config->import([
            'language' => 'en',
            'title' => 'Global title',
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US', 'config' => ['title' => 'English title']],
                ['code' => 'fr', 'locale' => 'fr_FR', 'config' => []],
            ],
        ]);

        self::assertSame('Site title', $config->get('title', 'fr', false));
    }

    public function testGetOutputFormatPropertyThrowsWhenPropertyIsUnknown(): void
    {
        $config = new Config();
        $config->import([]);

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Property "unknown" is not defined for format "html".');

        $config->getOutputFormatProperty('html', 'unknown');
    }

    public function testOutputFormatPropertyReturnsMediaType(): void
    {
        $config = new Config();
        $config->import([]);

        self::assertSame('text/html', $config->getOutputFormatProperty('html', 'mediatype'));
    }

    public function testPathHelpersRespectConfiguredDirectories(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$source, $destination]);

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import([]);

        self::assertStringEndsWith('pages', $config->getPagesPath());
        self::assertStringEndsWith('_site', $config->getOutputPath());
        self::assertStringEndsWith('data', $config->getDataPath());
        self::assertStringEndsWith('layouts', $config->getLayoutsPath());
        self::assertStringEndsWith('translations', $config->getTranslationsPath());
        self::assertStringEndsWith('themes', $config->getThemesPath());
        self::assertStringEndsWith('static', $config->getStaticPath());
        self::assertStringEndsWith('assets', $config->getAssetsPath());
        self::assertStringEndsWith('remote', $config->getAssetsRemotePath());
    }

    public function testAbsoluteCachePathCreatesCecilSubdirectory(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $absoluteCacheRoot = $this->tmpDir . DIRECTORY_SEPARATOR . 'cache-root';
        $this->filesystem->mkdir([$source, $destination, $absoluteCacheRoot]);

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import([
            'cache' => [
                'enabled' => true,
                'dir' => $absoluteCacheRoot,
            ],
        ]);

        $cachePath = $config->getCachePath();

        self::assertStringEndsWith('cache-root' . DIRECTORY_SEPARATOR . 'cecil', $cachePath);
        self::assertDirectoryExists($cachePath);
    }

    public function testLanguageHelpersReturnExpectedValues(): void
    {
        $config = new Config();
        $config->import([
            'language' => ['code' => 'en'],
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US', 'name' => 'English'],
                ['code' => 'fr', 'locale' => 'fr_FR', 'name' => 'Francais'],
            ],
        ]);

        self::assertSame('en', $config->getLanguageDefault());
        self::assertSame(1, $config->getLanguageIndex('fr'));
        self::assertSame('fr_FR', $config->getLanguageProperty('locale', 'fr'));
        self::assertCount(2, $config->getLanguages());
    }

    public function testLanguageDefaultMustExistInLanguagesList(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('is not listed in "languages"');

        $config = new Config();
        $config->import([
            'language' => 'en',
            'languages' => [
                ['code' => 'fr', 'locale' => 'fr_FR'],
            ],
        ]);
        $config->getLanguages();
    }

    public function testSetSourceDirAndSetDestinationDirValidateDirectories(): void
    {
        $config = new Config();
        $missing = $this->tmpDir . DIRECTORY_SEPARATOR . 'missing-dir';

        try {
            $config->setSourceDir($missing);
            self::fail('Expected exception was not thrown for source dir.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('is not a valid source', $e->getMessage());
        }

        try {
            $config->setDestinationDir($missing);
            self::fail('Expected exception was not thrown for destination dir.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('is not a valid destination', $e->getMessage());
        }
    }

    public function testIsEnabledSupportsBooleanAndEnabledSubkey(): void
    {
        $config = new Config();
        $config->import([
            'featureA' => true,
            'featureB' => ['enabled' => true],
            'featureC' => ['enabled' => false],
            'featureD' => 'custom',
        ]);

        self::assertTrue($config->isEnabled('featureA'));
        self::assertTrue($config->isEnabled('featureB'));
        self::assertFalse($config->isEnabled('featureC'));
        self::assertTrue($config->isEnabled('featureD'));
        self::assertFalse($config->isEnabled('missingFeature'));
    }

    public function testThemeHelpersAndMissingThemeValidation(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$source, $destination]);

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import([]);

        self::assertNull($config->getTheme());
        self::assertFalse($config->hasTheme());

        $config->import(['theme' => 'missing-theme']);
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Theme "missing-theme" not found');
        $config->hasTheme();
    }

    public function testCacheAndInternalPathsHelpers(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$source, $destination]);

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import([]);

        self::assertStringEndsWith('templates', $config->getCacheTemplatesPath());
        self::assertStringEndsWith('translations', $config->getCacheTranslationsPath());
        self::assertStringEndsWith('assets', $config->getCacheAssetsPath());
        self::assertStringEndsWith('resources' . DIRECTORY_SEPARATOR . 'layouts', $config->getLayoutsInternalPath());
        self::assertStringEndsWith('resources' . DIRECTORY_SEPARATOR . 'translations', $config->getTranslationsInternalPath());
    }

    public function testLayoutSectionAndThemeDirPathHelpers(): void
    {
        $config = new Config();
        $config->import([
            'layouts' => [
                'sections' => [
                    'blog' => 'custom-blog',
                ],
            ],
        ]);

        self::assertSame('custom-blog', $config->getLayoutSection('blog'));
        self::assertSame('news', $config->getLayoutSection('news'));
        self::assertStringEndsWith('themes' . DIRECTORY_SEPARATOR . 'my-theme' . DIRECTORY_SEPARATOR . 'assets', $config->getThemeDirPath('my-theme', 'assets'));
    }

    public function testLanguageValidationErrorsAreRaised(): void
    {
        try {
            new Config([
                'language' => 'bad_code',
            ]);
            self::fail('Expected invalid language code exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('Default language code', $e->getMessage());
        }

        try {
            new Config([
                'languages' => [
                    ['code' => 'en'],
                ],
            ]);
            self::fail('Expected missing locale exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('locale is not defined', $e->getMessage());
        }

        try {
            new Config([
                'languages' => [
                    ['code' => 'en', 'locale' => 'invalid-locale'],
                ],
            ]);
            self::fail('Expected invalid locale exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('language locale', $e->getMessage());
        }
    }

    public function testDeprecatedOptionsRaiseDetailedErrors(): void
    {
        try {
            new Config([
                'frontmatter' => 'yaml',
            ]);
            self::fail('Expected deprecated move exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('must be moved to', $e->getMessage());
            self::assertStringContainsString('pages:', $e->getMessage());
        }

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('deprecated and must be removed');

        new Config([
            'assets' => [
                'remote' => [
                    'dir' => '/tmp',
                ],
            ],
        ]);
    }

    public function testEnvironmentVariablesOverrideConfigurationAndCastBooleans(): void
    {
        putenv('CECIL_TITLE=Env Title');
        putenv('CECIL_CANONICALURL=true');

        try {
            $config = new Config();
            $config->import([]);

            self::assertSame('Env Title', $config->get('title'));
            self::assertTrue($config->get('canonicalurl'));
        } finally {
            putenv('CECIL_TITLE');
            putenv('CECIL_CANONICALURL');
        }
    }

    public function testExportContainsImportedValues(): void
    {
        $config = new Config();
        $config->import(['title' => 'Exported title']);

        $export = $config->export();

        self::assertIsArray($export);
        self::assertSame('Exported title', $export['title']);
    }

    public function testSourceAndDestinationFallbacksUseCurrentWorkingDirectory(): void
    {
        $config = new Config();

        self::assertSame(getcwd(), $config->getSourceDir());
        self::assertSame(getcwd(), $config->getDestinationDir());
    }

    public function testGetOutputFormatPropertyReturnsNullForUnknownFormat(): void
    {
        $config = new Config();
        $config->import([]);

        self::assertNull($config->getOutputFormatProperty('unknown-format', 'mediatype'));
    }

    public function testAssetsResponsiveHelpersReturnConfiguredValues(): void
    {
        $config = new Config();
        $config->import([]);

        self::assertNotEmpty($config->getAssetsImagesWidths());
        self::assertArrayHasKey('default', $config->getAssetsImagesSizes());
        self::assertNotEmpty($config->getAssetsImagesDensities());
    }

    public function testLanguageHelpersRaiseExceptionsForMissingValues(): void
    {
        try {
            new Config([
                'language' => [],
            ]);
            self::fail('Expected missing language.code exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('There is no default "language" key.', $e->getMessage());
        }

        $config = new Config();
        $config->import([]);

        try {
            $config->getLanguageIndex('zz');
            self::fail('Expected undefined language code exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('is not defined', $e->getMessage());
        }

        try {
            $config->getLanguageProperty('unknown_property');
            self::fail('Expected missing language property exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('Property "unknown_property" is not defined', $e->getMessage());
        }
    }

    public function testGetLanguagesFiltersDisabledEntries(): void
    {
        $config = new Config();
        $config->import([
            'language' => 'en',
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US', 'enabled' => true],
                ['code' => 'fr', 'locale' => 'fr_FR', 'enabled' => false],
            ],
        ]);

        $languages = $config->getLanguages();

        self::assertCount(1, $languages);
        self::assertSame('en', $languages[0]['code']);
    }

    public function testHasThemeReturnsTrueWhenThemeConfigExists(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $themeDir = $source . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'existing-theme';
        $this->filesystem->mkdir([$source, $destination, $themeDir]);
        file_put_contents($themeDir . DIRECTORY_SEPARATOR . 'config.yml', "title: Theme\n");

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import(['theme' => 'existing-theme']);

        self::assertTrue($config->hasTheme());
    }

    public function testHasWithLanguageAndFallbackDisabledUsesDefaultPresence(): void
    {
        $config = new Config();
        $config->import([
            'language' => 'en',
            'languages' => [
                ['code' => 'en', 'locale' => 'en_US', 'config' => []],
                ['code' => 'fr', 'locale' => 'fr_FR', 'config' => []],
            ],
        ]);

        self::assertTrue($config->has('title', 'fr', false));
    }

    public function testGetLanguagePropertyUsesDefaultLanguageWhenCodeIsNull(): void
    {
        $config = new Config();
        $config->import([]);

        self::assertSame('en_EN', $config->getLanguageProperty('locale'));
    }

    public function testGetOutputFormatPropertyCanReturnArrayProperty(): void
    {
        $config = new Config();
        $config->import([]);

        $exclude = $config->getOutputFormatProperty('atom', 'exclude');

        self::assertIsArray($exclude);
        self::assertContains('redirect', $exclude);
    }

    public function testIsCacheDirIsAbsoluteReturnsFalseForRelativeCacheDir(): void
    {
        $config = new Config();
        $config->import([
            'cache' => [
                'dir' => '.cache',
            ],
        ]);

        self::assertFalse($config->isCacheDirIsAbsolute());
    }

    public function testGetCachePathUsesDestinationForRelativeCacheDirectory(): void
    {
        $source = $this->tmpDir . DIRECTORY_SEPARATOR . 'source';
        $destination = $this->tmpDir . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$source, $destination]);

        $config = new Config();
        $config->setSourceDir($source);
        $config->setDestinationDir($destination);
        $config->import([
            'cache' => [
                'enabled' => true,
                'dir' => '.cache',
            ],
        ]);

        self::assertSame($destination . DIRECTORY_SEPARATOR . '.cache', $config->getCachePath());
    }

    public function testOutputFormatValidationRequiresNameAndMediatype(): void
    {
        try {
            new Config([
                'output' => [
                    'formats' => [
                        ['mediatype' => 'text/plain'],
                    ],
                ],
            ]);
            self::fail('Expected missing name validation exception.');
        } catch (ConfigException $e) {
            self::assertStringContainsString('missing "name"', $e->getMessage());
        }

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('missing "mediatype"');

        new Config([
            'output' => [
                'formats' => [
                    ['name' => 'plain'],
                ],
            ],
        ]);
    }

    public function testLanguageCanBeOverriddenToEmptyViaEnvironment(): void
    {
        putenv('CECIL_LANGUAGE=');

        try {
            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage('There is no default "language" key.');

            $config = new Config();
            $config->import([]);
        } finally {
            putenv('CECIL_LANGUAGE');
        }
    }
}
