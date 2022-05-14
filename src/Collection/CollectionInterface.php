<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection;

/**
 * Interface CollectionInterface.
 */
interface CollectionInterface extends BaseInterface, \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Set the collection's identifier.
     */
    public function setId(string $id): BaseInterface;

    /**
     * Returns the collection's identifier.
     */
    public function getId(): string;

    /**
     * Does the item exists?
     */
    public function has(string $id): bool;

    /**
     * Add an item or throw an exception if exists.
     */
    public function add(ItemInterface $item): self;

    /**
     * Replaces an item or throw an exception if not exists.
     */
    public function replace(string $id, ItemInterface $item): self;

    /**
     * Removes an item or throw an exception if not exists.
     */
    public function remove(string $id): self;

    /**
     * Retrieves an item or throw an exception if not exists.
     */
    public function get(string $id): ItemInterface;

    /**
     * Retrieves an item position or throw an exception if not exists.
     */
    public function getPosition(string $id): int;

    /**
     * Retrieves all keys.
     */
    public function keys(): array;

    /**
     * Retrieves the first item.
     */
    public function first(): ?ItemInterface;

    /**
     * Implements Countable.
     */
    public function count(): int;

    /**
     * Returns collection as array.
     */
    public function toArray(): array;

    /**
     * Returns a JSON string of items.
     */
    public function toJson(): string;

    /**
     * Implements \IteratorAggregate.
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Implements usort.
     *
     * @param \Closure|null $callback
     */
    public function usort(\Closure $callback = null): self;

    /**
     * Filters items using a callback function.
     */
    public function filter(\Closure $callback): self;

    /**
     * Applies a callback to items.
     */
    public function map(\Closure $callback): self;
}
