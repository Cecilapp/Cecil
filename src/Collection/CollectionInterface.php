<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection;

/**
 * Interface CollectionInterface.
 */
interface CollectionInterface extends \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Set the collection's identifier.
     *
     * @param string|null $id
     *
     * @return self
     */
    public function setId(string $id = null);

    /**
     * Return the collection's identifier.
     *
     * @return string
     */
    public function getId();

    /**
     * Does the item exists?
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Add an item.
     *
     * @param ItemInterface $item
     *
     * @return self|null
     */
    public function add(ItemInterface $item): ?self;

    /**
     * Replace an item if exists.
     *
     * @param string $id
     * @param ItemInterface $item
     *
     * @return self|null
     */
    public function replace(string $id, ItemInterface $item): ?self;

    /**
     * Remove an item if exists.
     *
     * @param string $id
     *
     * @return self|null
     */
    public function remove(string $id): ?self;

    /**
     * Retrieve an item.
     *
     * @param string $id
     *
     * @return ItemInterface|bool
     */
    public function get(string $id): ?ItemInterface;

    /**
     * Retrieve all keys.
     *
     * @return array An array of all keys
     */
    public function keys(): array;

    /**
     * Implement Countable.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Return collection as array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Implement \IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Implements usort.
     *
     * @param \Closure|null $callback
     *
     * @return CollectionInterface
     */
    public function usort(\Closure $callback = null): CollectionInterface;

    /**
     * Filters items using a callback function.
     *
     * @param \Closure $callback
     *
     * @return CollectionInterface
     */
    public function filter(\Closure $callback): CollectionInterface;

    /**
     * Applies a callback to items.
     *
     * @param \Closure $callback
     *
     * @return CollectionInterface
     */
    public function map(\Closure $callback): CollectionInterface;
}
