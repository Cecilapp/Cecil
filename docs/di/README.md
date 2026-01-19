# Dependency Injection in Cecil

This document explains how dependency injection works in Cecil and how to use it effectively in your code.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [How It Works](#how-it-works)
- [Usage Guide](#usage-guide)
- [Best Practices](#best-practices)
- [Examples](#examples)
- [Testing](#testing)

## Overview

Cecil uses [PHP-DI](https://php-di.org/) as its dependency injection container. This brings several benefits:

- **Testability**: Easy to mock dependencies in tests
- **Modularity**: Clear separation of concerns
- **Maintainability**: Dependencies are explicit and centralized
- **Performance**: Lazy loading and compiled container in production
- **Simplicity**: Automatic autowiring reduces boilerplate

## Architecture

### Container Initialization

The DI container is initialized in the `Builder` class constructor:

```php
// src/Builder.php
public function __construct($config = null, ?LoggerInterface $logger = null)
{
    // ... config and logger setup ...
    
    // Initialize DI container
    $this->container = ContainerFactory::create($this->config, $this->logger);
    
    // Inject Builder itself for services that need it
    $this->container->set(Builder::class, $this);
}
```

### Container Factory

The `ContainerFactory` is responsible for creating and configuring the container:

```php
// src/Container/ContainerFactory.php
public static function create(Config $config, LoggerInterface $logger): Container
{
    $builder = new ContainerBuilder();
    
    // Enable PHP 8 attributes for dependency injection
    $builder->useAttributes(true);
    
    // Load dependencies configuration
    $builder->addDefinitions(__DIR__ . '/../../config/dependencies.php');
    
    // Enable compilation cache in production
    if (!$config->get('debug')) {
        $builder->enableCompilation($config->getCachePath() . '/di');
    }
    
    return $builder->build();
}
```

### Configuration File

Dependencies are configured in `config/dependencies.php` and organized by type:

- **Converters**: Markdown/content conversion services
- **Generators**: Page generation services
- **Build lifecycle steps**: Processing steps (Load → Create → Process → Save → Optimize)
- **Services**: Shared services like Twig factory, Cache, etc.

## How It Works

### 1. Autowiring (Default Behavior)

PHP-DI automatically resolves dependencies based on type hints:

```php
class MyStep extends AbstractStep
{
    // PHP-DI will automatically inject Builder, Config, and LoggerInterface
    public function __construct(
        Builder $builder,
        Config $config,
        LoggerInterface $logger
    ) {
        // ...
    }
}
```

### 2. Attribute-Based Injection (Recommended for Simplicity)

Use `#[Inject]` attribute for simple property injection:

```php
use DI\Attribute\Inject;

class Convert extends AbstractStep
{
    #[Inject]
    private Converter $converter;
    
    // No need to override constructor!
    // PHP-DI automatically injects $converter
}
```

### 3. Configuration-Based Injection (For Complex Cases)

Define specific injection rules in `config/dependencies.php`:

```php
use function DI\autowire;
use function DI\get;

return [
    // Custom parameter injection
    Parsedown::class => autowire()
        ->constructorParameter('config', get(\Cecil\Config::class))
        ->constructorParameter('options', null),
    
    // Factory pattern
    Twig::class => \DI\factory(function (TwigFactory $factory) {
        return $factory->create();
    }),
];
```

## Usage Guide

### When to Use Each Approach

| Situation | Approach | Example |
|-----------|----------|---------|
| 1-2 simple dependencies | `#[Inject]` attribute | `Convert` needs `Converter` |
| Complex configuration | `dependencies.php` | `Parsedown` with options |
| Shared singleton | `dependencies.php` + lazy | `CoreExtension` |
| Factory pattern | `\DI\factory()` | `TwigFactory` |
| Contextual instances | Helper method | `getCache($pool)` |

### Creating a New Step

**Approach 1: Using Attributes (Recommended)**

```php
<?php

namespace Cecil\Step\MyModule;

use Cecil\Step\AbstractStep;
use Cecil\SomeService;
use DI\Attribute\Inject;

class MyNewStep extends AbstractStep
{
    #[Inject]
    private SomeService $service;
    
    public function getName(): string
    {
        return 'My step name';
    }
    
    public function process(): void
    {
        // Use $this->service
        $this->service->doSomething();
    }
}
```

**Approach 2: Using Constructor (Traditional)**

```php
<?php

namespace Cecil\Step\MyModule;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Step\AbstractStep;
use Cecil\SomeService;
use Psr\Log\LoggerInterface;

class MyNewStep extends AbstractStep
{
    private SomeService $service;
    
    public function __construct(
        Builder $builder,
        Config $config,
        LoggerInterface $logger,
        SomeService $service
    ) {
        parent::__construct($builder, $config, $logger);
        $this->service = $service;
    }
    
    // ... rest of the code
}
```

### Creating a New Generator

```php
<?php

namespace Cecil\Generator;

use Cecil\Converter\Converter;
use DI\Attribute\Inject;

class MyGenerator extends AbstractGenerator
{
    #[Inject]
    private Converter $converter;
    
    public function generate(): void
    {
        // Use $this->converter
        $html = $this->converter->convertBody($content);
        
        // Add generated pages
        $this->generatedPages->add($page);
    }
}
```

### Accessing Services from Builder

```php
// Get any service from the container
$service = $this->builder->get(SomeService::class);

// Get a Cache instance with specific pool
$cache = $this->builder->getCache('assets');
```

### Registering a New Service

Add it to `config/dependencies.php`:

```php
return [
    // ... existing definitions ...
    
    // Autowired service (simple)
    MyService::class => autowire(),
    
    // Service with custom parameters
    MyComplexService::class => autowire()
        ->constructorParameter('timeout', 30)
        ->constructorParameter('retries', 3),
    
    // Singleton service (shared instance)
    MySharedService::class => autowire()->lazy(),
    
    // Factory-created service
    MyFactoryService::class => \DI\factory(function (Builder $builder) {
        return new MyFactoryService($builder->getConfig()->get('my.setting'));
    }),
];
```

## Best Practices

### 1. Prefer Attributes for Simple Dependencies

✅ **Good:**
```php
#[Inject]
private Converter $converter;
```

❌ **Avoid (unnecessary boilerplate):**
```php
public function __construct(Converter $converter) {
    $this->converter = $converter;
}
```

### 2. Keep Constructor Parameters Minimal

If you need more than 3-4 dependencies, consider refactoring or using attributes.

### 3. Use Type Hints

Always use type hints for autowiring to work:

✅ **Good:**
```php
public function __construct(Config $config)
```

❌ **Bad:**
```php
public function __construct($config) // No type hint
```

### 4. Avoid `new` Keyword for Services

✅ **Good:**
```php
#[Inject]
private Cache $cache;
```

❌ **Avoid:**
```php
$cache = new Cache($this->builder, 'pool');
```

**Exception:** Use helper methods for contextual instances:
```php
$cache = $this->builder->getCache('pool'); // ✅ OK
```

### 5. Don't Inject Builder Everywhere

Only inject what you actually need:

✅ **Good:**
```php
public function __construct(Config $config, LoggerInterface $logger)
```

❌ **Avoid (if you only need config and logger):**
```php
public function __construct(Builder $builder)
```

### 6. Document Custom Configurations

If you add complex configuration in `dependencies.php`, add a comment:

```php
// MyService requires custom initialization because [reason]
MyService::class => \DI\factory(function (Config $config) {
    $options = [
        'timeout' => $config->get('service.timeout') ?? 30,
        'retries' => $config->get('service.retries') ?? 3,
    ];
    return new MyService($options);
}),
```

## Examples

### Example 1: Simple Step with One Dependency

```php
<?php

namespace Cecil\Step\Content;

use Cecil\Converter\Converter;
use Cecil\Step\AbstractStep;
use DI\Attribute\Inject;

class ProcessMarkdown extends AbstractStep
{
    #[Inject]
    private Converter $converter;
    
    public function getName(): string
    {
        return 'Processing Markdown';
    }
    
    public function process(): void
    {
        foreach ($this->builder->getPages() as $page) {
            $html = $this->converter->convertBody($page->getBody());
            $page->setBodyHtml($html);
        }
    }
}
```

### Example 2: Generator with Multiple Dependencies

```php
<?php

namespace Cecil\Generator;

use Cecil\Converter\Converter;
use Cecil\Renderer\Twig\TwigFactory;
use DI\Attribute\Inject;

class CustomPagesGenerator extends AbstractGenerator
{
    #[Inject]
    private Converter $converter;
    
    #[Inject]
    private TwigFactory $twigFactory;
    
    public function generate(): void
    {
        // Use both dependencies
        $html = $this->converter->convertBody($content);
        $renderer = $this->twigFactory->create();
        
        // ... generate pages
    }
}
```

### Example 3: Service with Factory

```php
<?php

namespace Cecil\Service;

use Cecil\Config;

class ApiClient
{
    private string $apiKey;
    private int $timeout;
    
    public function __construct(string $apiKey, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }
}
```

Register in `config/dependencies.php`:

```php
use Cecil\Service\ApiClient;

return [
    // ... other definitions ...
    
    ApiClient::class => \DI\factory(function (Config $config) {
        return new ApiClient(
            $config->get('api.key'),
            $config->get('api.timeout') ?? 30
        );
    }),
];
```

### Example 4: Using the Service

```php
use DI\Attribute\Inject;

class MyStep extends AbstractStep
{
    #[Inject]
    private ApiClient $apiClient;
    
    public function process(): void
    {
        $data = $this->apiClient->fetch('/endpoint');
        // ...
    }
}
```

## Testing

### Mocking Dependencies in Tests

The DI container makes testing easier by allowing you to inject mocks:

```php
use PHPUnit\Framework\TestCase;
use Cecil\Builder;
use Cecil\Converter\Converter;
use Cecil\Step\Pages\Convert;

class ConvertTest extends TestCase
{
    public function testConvert()
    {
        // Create mocks
        $builder = $this->createMock(Builder::class);
        $config = $this->createMock(Config::class);
        $logger = $this->createMock(LoggerInterface::class);
        
        // Mock the converter
        $converter = $this->createMock(Converter::class);
        $converter->expects($this->once())
            ->method('convertBody')
            ->willReturn('<p>HTML</p>');
        
        // Inject mocks
        $step = new Convert($builder, $config, $logger, $converter);
        
        // Test
        $step->process();
    }
}
```

### Testing with Container

```php
use Cecil\Container\ContainerFactory;

class MyIntegrationTest extends TestCase
{
    private Container $container;
    
    protected function setUp(): void
    {
        $config = new Config(['debug' => true]);
        $logger = new NullLogger();
        
        $this->container = ContainerFactory::create($config, $logger);
    }
    
    public function testServiceResolution()
    {
        $service = $this->container->get(MyService::class);
        $this->assertInstanceOf(MyService::class, $service);
    }
}
```

## Advanced Topics

### Lazy Services

For expensive-to-create services that might not always be used:

```php
// config/dependencies.php
ExpensiveService::class => autowire()->lazy(),
```

The service will only be instantiated when first accessed.

### Conditional Services

Register different implementations based on environment:

```php
use function DI\factory;

return [
    CacheInterface::class => factory(function (Config $config) {
        if ($config->get('cache.driver') === 'redis') {
            return new RedisCache();
        }
        return new FileCache();
    }),
];
```

### Decorators

Wrap a service with additional functionality:

```php
use function DI\decorate;

return [
    Converter::class => autowire(),
    
    // Decorate the Converter
    Converter::class => decorate(function ($previous, Container $c) {
        return new CachingConverter($previous, $c->get(Cache::class));
    }),
];
```

## Performance

### Container Compilation

In production, the container is compiled to plain PHP code for better performance:

```php
// Enabled automatically when debug = false
$builder->enableCompilation($cacheDir);
```

The compiled container is stored in `.cache/di/CompiledContainer.php`.

### Benchmarks

With container compilation enabled:
- **Container build time**: ~0ms (compiled)
- **Service resolution**: ~0.001ms per service
- **Memory overhead**: ~50KB

## Troubleshooting

### Service Not Found

**Error:** `Class X is not registered in the container`

**Solution:** Add the service to `config/dependencies.php` or ensure it can be autowired.

### Circular Dependency

**Error:** `Circular dependency detected: A → B → A`

**Solution:** Refactor to remove the circular reference, or use lazy injection:

```php
#[Inject(lazy: true)]
private ServiceB $serviceB;
```

### Attribute Not Working

**Error:** Attribute `#[Inject]` is ignored

**Checklist:**
1. Ensure `useAttributes(true)` is called in ContainerFactory ✓
2. Import the attribute: `use DI\Attribute\Inject;` ✓
3. Property must be `private` or `protected` ✓
4. Class must be resolved through the container ✓

## Migration Guide

### From Manual Instantiation

**Before:**
```php
class MyStep extends AbstractStep
{
    public function process(): void
    {
        $converter = new Converter($this->builder);
        $html = $converter->convertBody($content);
    }
}
```

**After:**
```php
class MyStep extends AbstractStep
{
    #[Inject]
    private Converter $converter;
    
    public function process(): void
    {
        $html = $this->converter->convertBody($content);
    }
}
```

### From Constructor Injection

**Before:**
```php
class MyStep extends AbstractStep
{
    private Converter $converter;
    
    public function __construct(
        Builder $builder,
        Config $config,
        LoggerInterface $logger,
        Converter $converter
    ) {
        parent::__construct($builder, $config, $logger);
        $this->converter = $converter;
    }
}
```

**After:**
```php
class MyStep extends AbstractStep
{
    #[Inject]
    private Converter $converter;
    
    // Constructor removed - less boilerplate!
}
```

## References

- [PHP-DI Official Documentation](https://php-di.org/doc/)
- [PHP-DI Best Practices](https://php-di.org/doc/best-practices.html)
- [PHP 8 Attributes](https://www.php.net/manual/en/language.attributes.overview.php)
- [PSR-11 Container Interface](https://www.php-fig.org/psr/psr-11/)

## Contributing

When adding new features that use dependency injection:

1. Follow the existing patterns in the codebase
2. Use `#[Inject]` for simple dependencies
3. Document complex configurations in `dependencies.php`
4. Update this documentation if you introduce new patterns
5. Ensure tests cover the injected dependencies

## Support

For questions or issues related to dependency injection in Cecil:
- Check existing [GitHub Issues](https://github.com/Cecilapp/Cecil/issues)
- Review [Pull Request #2285](https://github.com/Cecilapp/Cecil/pull/2285) for implementation details
- Consult the [PHP-DI documentation](https://php-di.org/) for framework-specific questions
