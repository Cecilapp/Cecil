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

namespace Cecil\Extension;

use Cecil\BuildContextInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Util;

/**
 * Extensions loader.
 *
 * Loads the extensions (generators, Twig extensions, post-processors, etc.) declared in a configuration key,
 * as a map of entries indexed by name:
 * - `<name>: <class>`
 * - `<name>: {class: <class>, priority: <int>, enabled: <bool>}`
 * - `<name>: false` (disabled)
 *
 * Extensions are sorted by ascending priority (declaration order for equal priorities).
 * An invalid extension fails the build if `extensions.strict` is enabled, otherwise the error is logged and the extension skipped
 * (legacy entries indexed by priority or position are still supported, with a deprecation warning).
 */
class Loader
{
    /**
     * Default priority of an extension: after built-in extensions.
     */
    public const int DEFAULT_PRIORITY = 100;

    /**
     * Allowed keys of an extension entry.
     */
    private const array ENTRY_KEYS = ['class', 'priority', 'enabled'];

    public function __construct(protected BuildContextInterface $builder)
    {
    }

    /**
     * Returns the valid and enabled extensions definitions of the configuration `$key`, sorted by priority.
     *
     * @template T of object
     *
     * @param class-string<T> $interface Interface (or parent class) that extensions must implement
     *
     * @return list<array{name: string, class: class-string<T>, priority: int}>
     *
     * @throws RuntimeException
     */
    public function getDefinitions(string $key, string $interface): array
    {
        // indexed by name: a legacy entry (indexed by priority) can replace a built-in extension
        $definitions = [];
        foreach ((array) $this->builder->getConfig()->get($key) as $name => $entry) {
            try {
                $definition = $this->normalize($key, $name, $entry);
                if ($definition === null) {
                    $this->builder->getLogger()->debug(\sprintf('Extension "%s" (%s) disabled', $name, $key));
                    continue;
                }
                $class = $definition['class'];
                if (!class_exists($class)) {
                    throw new RuntimeException(\sprintf('class "%s" not found.', $class));
                }
                if (!is_a($class, $interface, true)) {
                    throw new RuntimeException(\sprintf('class "%s" must implement "%s".', $class, $interface));
                }
                $definitions[$definition['name']] = ['name' => $definition['name'], 'class' => $class, 'priority' => $definition['priority']];
            } catch (RuntimeException $e) {
                $this->handleError($key, (string) $name, $e);
            }
        }
        $definitions = array_values($definitions);
        usort($definitions, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return $definitions;
    }

    /**
     * Returns the instances of valid and enabled extensions of the configuration `$key`, indexed by name and sorted by priority.
     * Each extension is instantiated with the Builder as argument.
     *
     * @template T of object
     *
     * @param class-string<T> $interface Interface (or parent class) that extensions must implement
     *
     * @return array<string, T>
     *
     * @throws RuntimeException
     */
    public function load(string $key, string $interface): array
    {
        $extensions = [];
        foreach ($this->getDefinitions($key, $interface) as $definition) {
            try {
                $extensions[$definition['name']] = new $definition['class']($this->builder);
                $this->builder->getLogger()->debug(\sprintf('Extension "%s" (%s) loaded', $definition['name'], $key));
            } catch (\Error $e) {
                $this->handleError($key, $definition['name'], new RuntimeException($e->getMessage(), previous: $e));
            }
        }

        return $extensions;
    }

    /**
     * Normalizes an extension entry, or returns null if it is disabled.
     *
     * @return array{name: string, class: string, priority: int}|null
     *
     * @throws RuntimeException
     */
    protected function normalize(string $key, int|string $name, mixed $entry): ?array
    {
        if (\is_int($name)) {
            return $this->normalizeLegacy($key, $name, $entry);
        }
        $entry = $this->toMap($entry);
        if ($entry === null || ($entry['enabled'] ?? true) === false) {
            return null;
        }

        return ['name' => $name] + $this->validate($entry);
    }

    /**
     * Normalizes a legacy extension entry, indexed by its priority (generators) or by its position (deprecated):
     * it is named after the built-in generator with the same priority (that it replaces), or after its short class name.
     *
     * @deprecated since Cecil 9.25, removed in Cecil 10
     *
     * @return array{name: string, class: string, priority: int}
     *
     * @throws RuntimeException
     */
    private function normalizeLegacy(string $key, int $index, mixed $entry): array
    {
        if (!\is_string($entry) || trim($entry) === '') {
            throw new RuntimeException('extensions must be indexed by name.');
        }
        $class = ltrim($entry, '\\');
        $priority = $key === 'pages.generators' ? $index : self::DEFAULT_PRIORITY;
        $builtIns = $this->getBuiltIns($key);
        $name = $key === 'pages.generators' ? array_search($priority, $builtIns, true) : false;
        if ($name === false) {
            $name = basename(str_replace('\\', '/', $class));
            // doesn't replace a built-in extension with the same short class name
            if (isset($builtIns[$name])) {
                $name .= "-$index";
            }
        }
        $this->builder->getLogger()->warning(\sprintf(
            'Extension "%s" (%s) is indexed by %s, which is deprecated and will not be supported in Cecil 10: use `%s: %s` instead.',
            $class,
            $key,
            $key === 'pages.generators' ? 'its priority' : 'its position',
            $name,
            $key === 'pages.generators' ? "{class: $class, priority: $priority}" : $class
        ));

        return ['name' => $name, 'class' => $class, 'priority' => $priority];
    }

    /**
     * Returns the priorities of the built-in extensions of the configuration `$key`, indexed by name.
     *
     * @return array<string, int>
     */
    private function getBuiltIns(string $key): array
    {
        $defaults = require Util\File::getRealPath('../config/default.php');
        foreach (explode('.', $key) as $fragment) {
            $defaults = $defaults[$fragment] ?? [];
        }
        $builtIns = [];
        foreach ((array) $defaults as $name => $entry) {
            $builtIns[(string) $name] = \is_array($entry) ? (int) ($entry['priority'] ?? self::DEFAULT_PRIORITY) : self::DEFAULT_PRIORITY;
        }

        return $builtIns;
    }

    /**
     * Converts an extension entry to a map of options, or returns null if it is disabled (`false`).
     *
     * @return array<mixed>|null
     *
     * @throws RuntimeException
     */
    private function toMap(mixed $entry): ?array
    {
        if ($entry === false || $entry === null) {
            return null;
        }
        if (\is_string($entry)) {
            return ['class' => $entry];
        }
        if (!\is_array($entry)) {
            throw new RuntimeException('an extension must be a class name, a map (`class`, `priority`, `enabled`) or `false`.');
        }
        if ($unknown = array_diff(array_keys($entry), self::ENTRY_KEYS)) {
            throw new RuntimeException(\sprintf('unknown option(s) "%s" (allowed: %s).', implode('", "', $unknown), implode(', ', self::ENTRY_KEYS)));
        }

        return $entry;
    }

    /**
     * Validates the options of an enabled extension, and returns its class and priority.
     *
     * @param array<mixed> $entry
     *
     * @return array{class: string, priority: int}
     *
     * @throws RuntimeException
     */
    private function validate(array $entry): array
    {
        if (!isset($entry['class']) || !\is_string($entry['class']) || trim($entry['class']) === '') {
            throw new RuntimeException('option "class" is required.');
        }
        if (isset($entry['priority']) && !\is_int($entry['priority'])) {
            throw new RuntimeException('option "priority" must be an integer.');
        }

        return [
            'class'    => ltrim($entry['class'], '\\'),
            'priority' => $entry['priority'] ?? self::DEFAULT_PRIORITY,
        ];
    }

    /**
     * Throws the error in strict mode, logs it otherwise.
     *
     * @throws RuntimeException
     */
    protected function handleError(string $key, string $name, RuntimeException $e): void
    {
        $message = \sprintf('Unable to load extension "%s" (%s): %s', $name, $key, $e->getMessage());
        $strict = $this->builder->getConfig()->get('extensions.strict');
        if ($strict === true) {
            throw new RuntimeException($message, previous: $e);
        }
        // not explicitly disabled: will fail the build in Cecil 10
        if ($strict === null) {
            $message .= ' (this will fail the build in Cecil 10: fix the extension, or set `extensions.strict` to `true` to fail now or to `false` to keep ignoring it)';
        }
        $this->builder->getLogger()->error($message);
    }
}
