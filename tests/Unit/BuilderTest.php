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
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
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

    public function testGetBuildIdThrowsTypeErrorBeforeBuildInitialization(): void
    {
        $this->expectException(\TypeError::class);

        Builder::getBuildId();
    }
}
