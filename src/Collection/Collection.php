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

namespace Cecil\Collection;

/**
 * Class Collection.
 *
 * Represents a collection of items, providing methods to manage them.
 */
class Collection implements CollectionInterface
{
    /**
     * Collection's identifier.
     * @var string
     */
    protected $id;
    /**
     * Collection's items.
     * @var array
     */
    protected $items = [];

    public function __construct(string $id, array $items = [])
    {
        $this->setId($id);
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): BaseInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Search an item by ID.
     */
    protected function searchItem(string $id): ?array
    {
        return array_filter($this->items, function (ItemInterface $item) use ($id) {
            return $item->getId() == $id;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException
     */
    public function getPosition(string $id): int
    {
        $result = $this->searchItem($id);
        $position = key($result);
        if (!\is_int($position)) {
            throw new \DomainException(\sprintf('"%s" does not exist in "%s" collection.', $id, $this->getId()));
        }

        return $position;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        $result = $this->searchItem($id);
        if (\is_array($result) && !empty($result)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException
     */
    public function add(ItemInterface $item): CollectionInterface
    {
        if ($this->has($item->getId())) {
            throw new \DomainException(\sprintf('Failed adding "%s" in "%s" collection: item already exists.', $item->getId(), $this->getId()));
        }
        $this->items[] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException
     */
    public function replace(string $id, ItemInterface $item): CollectionInterface
    {
        try {
            $this->items[$this->getPosition($id)] = $item;
        } catch (\DomainException) {
            throw new \DomainException(\sprintf('Failed replacing "%s" in "%s" collection: item does not exist.', $id, $this->getId()));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException
     */
    public function remove(string $id): CollectionInterface
    {
        try {
            unset($this->items[$this->getPosition($id)]);
        } catch (\DomainException) {
            throw new \DomainException(\sprintf('Failed removing "%s" in "%s" collection: item does not exist.', $id, $this->getId()));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \DomainException
     */
    public function get(string $id): ItemInterface
    {
        try {
            return $this->items[$this->getPosition($id)];
        } catch (\DomainException) {
            throw new \DomainException(\sprintf('Failed getting "%s" in "%s" collection: item does not exist.', $id, $this->getId()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function first(): ?ItemInterface
    {
        if (\count($this->items) < 1) {
            return null;
        }
        $items = $this->items;

        return array_shift($items);
    }

    /**
     * {@inheritdoc}
     */
    public function last(): ?ItemInterface
    {
        if (\count($this->items) < 1) {
            return null;
        }
        $items = $this->items;

        return array_pop($items);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson(): string
    {
        return \sprintf("%s\n", json_encode($this->items));
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(?\Closure $callback = null): CollectionInterface
    {
        $callback ? usort($this->items, $callback) : usort($this->items, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static($this->getId(), $this->items); /** @phpstan-ignore-line */
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(): CollectionInterface
    {
        return new static($this->getId(), array_reverse($this->items)); /** @phpstan-ignore-line */
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $callback): CollectionInterface
    {
        return new static($this->getId(), array_filter($this->items, $callback)); /** @phpstan-ignore-line */
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $callback): CollectionInterface
    {
        return new static($this->getId(), array_map($callback, $this->items)); /** @phpstan-ignore-line */
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param string $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param string $offset
     *
     * @return CollectionInterface|ItemInterface|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get((string) $offset);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed         $offset
     * @param ItemInterface $value
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->add($value);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param string $offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Returns the collection ID.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getId();
    }
}
