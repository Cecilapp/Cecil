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
 * An invalid extension fails the build, unless `extensions.strict` is disabled: the error is then logged and the extension skipped.
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
        $definitions = [];
        foreach ((array) $this->builder->getConfig()->get($key) as $name => $entry) {
            try {
                $definition = $this->normalize($name, $entry);
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
                $definitions[] = ['name' => $definition['name'], 'class' => $class, 'priority' => $definition['priority']];
            } catch (RuntimeException $e) {
                $this->handleError($key, (string) $name, $e);
            }
        }
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
    protected function normalize(int|string $name, mixed $entry): ?array
    {
        if (\is_int($name)) {
            $hint = \is_string($entry) ? \sprintf(' (e.g.: `%s: {class: %s, priority: %s}`)', basename(str_replace('\\', '/', $entry)), $entry, $name) : '';

            throw new RuntimeException(\sprintf('extensions must be indexed by name%s.', $hint));
        }
        $entry = $this->toMap($entry);
        if ($entry === null || ($entry['enabled'] ?? true) === false) {
            return null;
        }

        return ['name' => $name] + $this->validate($entry);
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
        if ($this->builder->getConfig()->isEnabled('extensions.strict')) {
            throw new RuntimeException($message, previous: $e);
        }
        $this->builder->getLogger()->error($message);
    }
}
