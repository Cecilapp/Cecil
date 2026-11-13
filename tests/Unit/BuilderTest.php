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

use Cecil\Asset;
use Cecil\Builder;
use Cecil\Collection\Menu\Collection as MenuCollection;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Taxonomy\Collection as TaxonomyCollection;
use Cecil\Collection\Taxonomy\Vocabulary;
use Cecil\Exception\ConfigException;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class BuilderTest extends TestCase
{
    public function testCreateReturnsBuilderInstance(): void
    {
        $builder = Builder::create(['baseurl' => 'https://example.com/']);

        self::assertInstanceOf(Builder::class, $builder);
    }

    public function testDataAccessorsAndLanguageFallback(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);
        $builder->setData([
            'en' => ['k' => 'en-value'],
            'fr' => ['k' => 'fr-value'],
        ]);

        self::assertSame(['k' => 'fr-value'], $builder->getData('fr'));
        self::assertSame(['k' => 'en-value'], $builder->getData('de'));
        self::assertIsArray($builder->getData());
    }

    public function testStaticAndPagesFilesAndPagesCollectionAccessors(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);

        $builder->setStatic(['a.css', 'b.js']);
        self::assertSame(['a.css', 'b.js'], $builder->getStatic());

        $finder = new Finder();
        $builder->setPagesFiles($finder);
        self::assertSame($finder, $builder->getPagesFiles());

        $pages = new PagesCollection('pages');
        $builder->setPages($pages);
        self::assertSame($pages, $builder->getPages());
    }

    public function testAssetsListDeduplicatesEntries(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);

        $builder->addToAssetsList('/css/app.css');
        $builder->addToAssetsList('/css/app.css');
        $builder->addToAssetsList('/js/app.js');

        self::assertSame(['/css/app.css', '/js/app.js'], $builder->getAssetsList());
    }

    public function testRememberAssetTracksHitsAndMisses(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);
        $asset = $this->createMock(Asset::class);

        $first = $builder->rememberAsset('k1', static fn (): Asset => $asset);
        $second = $builder->rememberAsset('k1', static fn (): Asset => $asset);

        self::assertSame($asset, $first);
        self::assertSame($asset, $second);

        $stats = $builder->getAssetRegistryStats();
        self::assertSame(1, $stats['hits']);
        self::assertSame(1, $stats['misses']);
        self::assertSame(2, $stats['total']);
        self::assertSame(50.0, $stats['deduplication_ratio']);
    }

    public function testRememberAssetFactoryMustReturnAsset(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('factory must return an Asset');

        $builder->rememberAsset('bad', static fn () => new \stdClass());
    }

    public function testLayoutCacheStatsAreTracked(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);

        $builder->recordLayoutCacheAccess(true);
        $builder->recordLayoutCacheAccess(false);
        $builder->recordLayoutCacheAccess(true);

        $stats = $builder->getLayoutCacheStats();
        self::assertSame(2, $stats['hits']);
        self::assertSame(1, $stats['misses']);
        self::assertSame(3, $stats['total']);
        self::assertSame(66.67, $stats['hit_rate']);
    }

    public function testMenusAndTaxonomiesAccessors(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);

        $menus = new MenuCollection('main');
        $builder->setMenus(['en' => $menus]);
        self::assertSame($menus, $builder->getMenus('en'));

        $taxonomies = new TaxonomyCollection('tax');
        $taxonomies->add(new Vocabulary('tags'));
        $builder->setTaxonomies(['en' => $taxonomies]);
        self::assertSame($taxonomies, $builder->getTaxonomies('en'));
    }

    public function testLoggerDebugAndVersionHelpers(): void
    {
        $logger = new PrintLogger(Builder::VERBOSITY_DEBUG);
        $builder = new Builder(['baseurl' => 'https://example.com/'], $logger);

        self::assertSame($logger, $builder->getLogger());
        self::assertFalse($builder->isDebug());
        self::assertNotSame('', Builder::getVersion());
        self::assertIsArray($builder->getMetrics());
        self::assertSame([], $builder->getBuildOptions());
    }

    public function testBuildIdIsSetWhenTheBuilderIsCreated(): void
    {
        new Builder(['baseurl' => 'https://example.com/'], new NullLogger());
        $buildId = Builder::getBuildId();
        new Builder(['baseurl' => 'https://example.com/'], new NullLogger());

        self::assertNotSame('', $buildId);
        // caches scoped to the build ID are not shared between Builders
        self::assertNotSame($buildId, Builder::getBuildId());
    }

    public function testBuildIdIsUniquePerBuild(): void
    {
        $sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-build-id-' . uniqid());
        (new Filesystem())->mkdir($sourceDir);
        $builder = (new Builder(['baseurl' => 'https://example.com/'], new NullLogger()))->setSourceDir($sourceDir);

        try {
            // builds of an empty site are fast: several of them start within the same second
            // (a fixed number of builds, rather than a time window, keeps the test independent of the machine load)
            $buildIds = [];
            for ($i = 0; $i < 5; $i++) {
                $builder->build(['dry-run' => true]);
                $buildIds[] = Builder::getBuildId();
            }

            self::assertCount(5, array_unique($buildIds));
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    public function testConfigOfThemesRequiredByAThemeIsImported(): void
    {
        $sourceDir = $this->createThemes([
            'visual'    => "theme: [component]\ntitle: Visual\ncomponent:\n  color: red\n",
            'component' => "theme: nested\ntitle: Component\ncomponent:\n  color: blue\n  size: 10\n",
            'nested'    => "nested:\n  enabled: true\n",
        ]);

        try {
            $config = (new Builder(['theme' => ['visual']], new NullLogger()))->setSourceDir($sourceDir)->getConfig();

            // required themes are added after the themes of the site
            self::assertSame(['visual', 'component', 'nested'], $config->getTheme());
            // their configuration is imported...
            self::assertSame(10, $config->get('component.size'));
            self::assertTrue($config->get('nested.enabled'));
            // ...and overridden by the theme requiring them
            self::assertSame('red', $config->get('component.color'));
            self::assertSame('Visual', $config->get('title'));
            self::assertTrue($config->hasTheme());
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    public function testThemesRequiredByAThemeAreAddedToAThemeSetAsString(): void
    {
        $sourceDir = $this->createThemes([
            'visual'    => "theme: [component]\n",
            'component' => "component:\n  size: 10\n",
        ]);

        try {
            $config = (new Builder(['theme' => 'visual'], new NullLogger()))->setSourceDir($sourceDir)->getConfig();

            self::assertSame(['visual', 'component'], $config->getTheme());
            self::assertSame(10, $config->get('component.size'));
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    public function testThemesOfTheSiteKeepTheirPriorityOverRequiredThemes(): void
    {
        $sourceDir = $this->createThemes([
            'first'    => "theme: [required]\n",
            'second'   => "title: Second\n",
            'required' => "title: Required\n",
        ]);

        try {
            $config = (new Builder(['theme' => ['first', 'second']], new NullLogger()))->setSourceDir($sourceDir)->getConfig();

            self::assertSame(['first', 'second', 'required'], $config->getTheme());
            self::assertSame('Second', $config->get('title'));
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    public function testThemesRequiringEachOtherAreImportedOnce(): void
    {
        $sourceDir = $this->createThemes([
            'a' => "theme: [b]\n",
            'b' => "theme: [a]\n",
        ]);

        try {
            $config = (new Builder(['theme' => ['a']], new NullLogger()))->setSourceDir($sourceDir)->getConfig();

            self::assertSame(['a', 'b'], $config->getTheme());
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    public function testMissingThemeRequiredByAThemeIsReportedWithItsRequirer(): void
    {
        $sourceDir = $this->createThemes([
            'visual' => "theme: [component]\n",
        ]);

        try {
            $config = (new Builder(['theme' => 'visual'], new NullLogger()))->setSourceDir($sourceDir)->getConfig();

            $this->expectException(ConfigException::class);
            $this->expectExceptionMessage('Theme "component" (required by theme "visual") not found');
            $config->hasTheme();
        } finally {
            (new Filesystem())->remove($sourceDir);
        }
    }

    /**
     * Creates a site with themes, and returns its directory.
     *
     * @param array<string, string> $themes Content of the `config.yml` file of each theme, indexed by theme name
     */
    private function createThemes(array $themes): string
    {
        $sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-themes-' . uniqid());
        foreach ($themes as $name => $config) {
            (new Filesystem())->dumpFile(Util::joinFile($sourceDir, 'themes', $name, 'config.yml'), $config);
        }

        return $sourceDir;
    }
}
