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

namespace Cecil\Plugin;

use Cecil\BuildContextInterface;
use Cecil\Config;
use Cecil\Extension\Loader;

/**
 * Plugin registry.
 *
 * Collects the extensions registered by a plugin, then adds them to the configuration with the lowest precedence:
 * the site and themes configuration can disable (`<name>: false`) or move (`<name>: {priority: <int>}`) them by name.
 */
class Registry
{
    /**
     * Configuration registered by the plugin.
     * @var array<string, mixed>
     */
    private array $config = [];

    public function __construct(protected BuildContextInterface $builder, protected string $plugin)
    {
    }

    /**
     * Registers a pages generator.
     */
    public function generator(string $class, int $priority = Loader::DEFAULT_PRIORITY, ?string $name = null): self
    {
        return $this->extension('pages.generators', $class, $priority, $name);
    }

    /**
     * Registers a Twig extension.
     */
    public function twigExtension(string $class, ?string $name = null): self
    {
        return $this->extension('layouts.extensions', $class, Loader::DEFAULT_PRIORITY, $name);
    }

    /**
     * Registers an output post-processor.
     */
    public function postProcessor(string $class, int $priority = Loader::DEFAULT_PRIORITY, ?string $name = null): self
    {
        return $this->extension('output.postprocessors', $class, $priority, $name);
    }

    /**
     * Registers a build events subscriber.
     */
    public function subscriber(string $class, ?string $name = null): self
    {
        return $this->extension('extensions.subscribers', $class, Loader::DEFAULT_PRIORITY, $name);
    }

    /**
     * Adds a build event listener.
     *
     * @see \Cecil\Event\BuildEvents
     */
    public function listen(string $eventName, callable $listener, int $priority = 0): self
    {
        $this->builder->getEventDispatcher()->addListener($eventName, $listener, $priority);

        return $this;
    }

    /**
     * Registers default configuration (e.g.: options of the plugin).
     *
     * @param array<string, mixed> $config
     */
    public function config(array $config): self
    {
        $this->config = array_replace_recursive($this->config, $config);

        return $this;
    }

    /**
     * Adds the registered configuration to the site configuration, with the lowest precedence.
     */
    public function apply(): void
    {
        if ($this->config === []) {
            return;
        }
        $this->builder->getConfig()->import($this->config, Config::IMPORT_PRESERVE, \sprintf('plugin "%s"', $this->plugin));
    }

    /**
     * Registers an extension in the given configuration key, indexed by its name (the short class name by default).
     */
    private function extension(string $key, string $class, int $priority, ?string $name): self
    {
        $name ??= basename(str_replace('\\', '/', $class));
        $entry = [$name => ['class' => $class, 'priority' => $priority]];
        foreach (array_reverse(explode('.', $key)) as $fragment) {
            $entry = [$fragment => $entry];
        }

        return $this->config($entry);
    }
}
