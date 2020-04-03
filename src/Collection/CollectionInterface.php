<?php
/**
 * This file is part of the Cecil/Cecil package.
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
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): BaseInterface;

    /**
     * Returns the collection's identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Does the item exists?
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Add an item or throw an exception if exists.
     *
     * @param ItemInterface $item
     *
     * @return self
     */
    public function add(ItemInterface $item): self;

    /**
     * Replaces an item or throw an exception if not exists.
     *
     * @param string        $id
     * @param ItemInterface $item
     *
     * @return self
     */
    public function replace(string $id, ItemInterface $item): self;

    /**
     * Removes an item or throw an exception if not exists.
     *
     * @param string $id
     *
     * @return self
     */
    public function remove(string $id): self;

    /**
     * Retrieves an item or throw an exception if not exists.
     *
     * @param string $id
     *
     * @return ItemInterface
     */
    public function get(string $id): ItemInterface;

    /**
     * Retrieves an item position or throw an exception if not exists.
     *
     * @param string $id
     *
     * @return int
     */
    public function getPosition(string $id): int;

    /**
     * Retrieves all keys.
     *
     * @return array An array of all keys
     */
    public function keys(): array;

    /**
     * Retrieves the first item.
     *
     * @return ItemInterface|null
     */
    public function first(): ?ItemInterface;

    /**
     * Implements Countable.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Returns collection as array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Returns a JSON string of items.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Implements \IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Implements usort.
     *
     * @param \Closure|null $callback
     *
     * @return self
     */
    public function usort(\Closure $callback = null): self;

    /**
     * Filters items using a callback function.
     *
     * @param \Closure $callback
     *
     * @return self
     */
    public function filter(\Closure $callback): self;

    /**
     * Applies a callback to items.
     *
     * @param \Closure $callback
     *
     * @return self
     */
    public function map(\Closure $callback): self;
}
