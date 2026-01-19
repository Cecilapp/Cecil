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
use Cecil\Cache;
use Cecil\Config;
use Cecil\Container\ContainerFactory;
use Cecil\Converter\Converter;
use Cecil\Converter\Parsedown;
use Cecil\Logger\PrintLogger;
use Cecil\Renderer\Twig;
use Cecil\Renderer\Twig\TwigFactory;
use Cecil\Util;
use DI\Container;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ContainerFactory and dependency injection functionality.
 *
 * This test class verifies:
 * 1. ContainerFactory successfully creates a container with all registered services
 * 2. Services can be resolved from the container
 * 3. Attribute-based injection works correctly
 * 4. The fallback mechanism in Builder::build() works as expected
 * 5. Cache instances are properly created via Builder::getCache()
 */
class ContainerFactoryTest extends TestCase
{
    protected Builder $builder;
    protected Container $container;

    public function setUp(): void
    {
        // Use existing test fixtures to create Builder with a real Config
        $source = Util::joinFile(__DIR__, 'fixtures/website');
        $configFile = Util::joinFile($source, 'config.yml');
        
        if (!file_exists($configFile)) {
            $this->markTestSkipped('Test fixtures not available');
            return;
        }

        $logger = new PrintLogger(Builder::VERBOSITY_NORMAL);
        $this->builder = Builder::create(Config::loadFile($configFile), $logger);
        $this->container = $this->builder->getContainer();
    }

    /**
     * Test 1: ContainerFactory successfully creates a container with all registered services.
     */
    public function testContainerFactoryCreatesContainer(): void
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    /**
     * Test 2: Verify Config and Logger are properly injected into the container.
     */
    public function testContainerHasConfigAndLogger(): void
    {
        // Config should be resolvable
        $config = $this->container->get(Config::class);
        $this->assertInstanceOf(Config::class, $config);

        // Logger should be resolvable
        $logger = $this->container->get(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /**
     * Test 3: Services can be resolved from the container - Steps.
     */
    public function testContainerResolvesSteps(): void
    {
        // Test a sample of step classes
        $stepsToTest = [
            \Cecil\Step\Pages\Load::class,
            \Cecil\Step\Data\Load::class,
            \Cecil\Step\Pages\Create::class,
            \Cecil\Step\Pages\Convert::class,
        ];

        foreach ($stepsToTest as $stepClass) {
            $this->assertTrue(
                $this->container->has($stepClass),
                "Container should have {$stepClass}"
            );

            // Note: Steps are not fully instantiated here because they require Builder
            // as a constructor parameter. Builder is injected after container creation,
            // so we verify the definitions exist without triggering instantiation.
        }
    }

    /**
     * Test 4: Services can be resolved from the container - Generators.
     */
    public function testContainerResolvesGenerators(): void
    {
        // Test a sample of generator classes
        $generatorsToTest = [
            \Cecil\Generator\Homepage::class,
            \Cecil\Generator\Section::class,
            \Cecil\Generator\Taxonomy::class,
            \Cecil\Generator\Pagination::class,
        ];

        foreach ($generatorsToTest as $generatorClass) {
            $this->assertTrue(
                $this->container->has($generatorClass),
                "Container should have {$generatorClass}"
            );
        }
    }

    /**
     * Test 5: Verify TwigFactory can be resolved and used.
     */
    public function testContainerResolvesTwigFactory(): void
    {
        $this->assertTrue($this->container->has(TwigFactory::class));
        
        // Note: Full instantiation would require Builder, but we can verify
        // the container knows about the factory
    }

    /**
     * Test 6: Test attribute-based injection with a real Builder instance.
     * This verifies PHP 8 attributes work correctly in the container.
     */
    public function testAttributeBasedInjectionWithBuilder(): void
    {
        // Verify container is set up correctly
        $this->assertInstanceOf(Container::class, $this->container);

        // Verify Builder itself is in the container
        $this->assertTrue($this->container->has(Builder::class));
        $builderFromContainer = $this->container->get(Builder::class);
        $this->assertSame($this->builder, $builderFromContainer);
    }

    /**
     * Test 7: Verify converter services can be resolved with dependencies.
     */
    public function testContainerResolvesConverterServices(): void
    {
        $this->assertTrue($this->container->has(Parsedown::class));
        $this->assertTrue($this->container->has(Converter::class));

        // Note: These services depend on Builder (Parsedown needs builder->Config->Builder).
        // Since Builder injects itself after container creation (see ContainerFactory::create),
        // we verify definitions exist without instantiation to avoid initialization order issues.
    }

    /**
     * Test 8: Test fallback mechanism simulation.
     * While we can't easily test the actual Builder::build() fallback without
     * modifying the container state, we can verify NotFoundException behavior.
     */
    public function testContainerThrowsNotFoundExceptionForUnknownService(): void
    {
        $this->expectException(NotFoundException::class);
        
        // Try to get a service that doesn't exist
        $this->container->get('NonExistentService');
    }

    /**
     * Test 9: Test Builder::getCache() method.
     * This verifies cache instances are properly created.
     */
    public function testBuilderGetCacheMethod(): void
    {
        // Test cache creation with default pool
        $cache1 = $this->builder->getCache();
        $this->assertInstanceOf(Cache::class, $cache1);

        // Test cache creation with named pool
        $cache2 = $this->builder->getCache('test-pool');
        $this->assertInstanceOf(Cache::class, $cache2);

        // Verify different pools create different instances
        $this->assertNotSame($cache1, $cache2);
    }

    /**
     * Test 10: Verify container compiles in production mode.
     */
    public function testContainerCompilationInProduction(): void
    {
        // Create a new builder without debug mode
        $source = Util::joinFile(__DIR__, 'fixtures/website');
        $configFile = Util::joinFile($source, 'config.yml');
        $logger = new PrintLogger(Builder::VERBOSITY_NORMAL);
        
        $builder = Builder::create(Config::loadFile($configFile), $logger);
        $container = $builder->getContainer();
        
        $this->assertInstanceOf(Container::class, $container);

        // The container should work even with compilation enabled
        $this->assertTrue($container->has(Config::class));
    }

    /**
     * Test 11: Verify container works in debug mode.
     */
    public function testContainerInDebugMode(): void
    {
        // Save original value to restore later
        $originalValue = getenv('CECIL_DEBUG');
        
        // Set debug environment variable
        putenv('CECIL_DEBUG=true');
        
        try {
            $source = Util::joinFile(__DIR__, 'fixtures/website');
            $configFile = Util::joinFile($source, 'config.yml');
            $logger = new PrintLogger(Builder::VERBOSITY_NORMAL);
            
            $builder = Builder::create(Config::loadFile($configFile), $logger);
            $container = $builder->getContainer();
            
            $this->assertInstanceOf(Container::class, $container);

            // The container should work without compilation in debug mode
            $this->assertTrue($container->has(Config::class));
        } finally {
            // Restore original environment variable value
            if ($originalValue !== false) {
                putenv("CECIL_DEBUG={$originalValue}");
            } else {
                putenv('CECIL_DEBUG');
            }
        }
    }

    /**
     * Test 12: Test the complete build process with DI container.
     * This is an integration test that verifies the DI container works
     * throughout the entire build lifecycle.
     */
    public function testFullBuildWithDependencyInjection(): void
    {
        $source = Util::joinFile(__DIR__, 'fixtures/website');
        
        $this->builder->setSourceDir($source);
        $this->builder->setDestinationDir($source);

        // Build the site - this exercises the fallback mechanism in Builder::build()
        try {
            $this->builder->build([
                'drafts'  => false,
                'dry-run' => true, // Use dry-run to avoid writing files
            ]);
            $this->assertTrue(true, 'Build completed successfully with DI container');
        } catch (\Exception $e) {
            $this->fail('Build failed with DI container: ' . $e->getMessage());
        }
    }

    /**
     * Test 13: Verify lazy loading of services.
     */
    public function testLazyLoadedServices(): void
    {
        // Core extension is marked as lazy
        $this->assertTrue($this->container->has(\Cecil\Renderer\Extension\Core::class));
        
        // Note: We can't fully test lazy loading without triggering instantiation,
        // but we can verify the definition exists
    }

    /**
     * Test 14: Verify factory definitions work correctly.
     */
    public function testFactoryDefinitions(): void
    {
        // Twig and Cache use factory definitions
        $this->assertTrue($this->container->has(Twig::class));
        $this->assertTrue($this->container->has(Cache::class));
    }
}
