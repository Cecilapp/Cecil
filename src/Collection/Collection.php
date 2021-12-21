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
 * Class Collection.
 */
class Collection implements CollectionInterface
{
    /** @var string Collection's identifier. */
    protected $id;

    /** @var array Collection's items. */
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
     */
    public function has(string $id): bool
    {
        $result = $this->searchItem($id);
        if (is_array($result) && !empty($result)) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ItemInterface $item): CollectionInterface
    {
        if ($this->has($item->getId())) {
            throw new \DomainException(sprintf(
                'Failed adding "%s" in "%s" collection: item already exists.',
                $item->getId(),
                $this->getId()
            ));
        }
        $this->items[] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $id, ItemInterface $item): CollectionInterface
    {
        if (!$this->has($id)) {
            throw new \DomainException(sprintf(
                'Failed replacing "%s" in "%s" collection: item does not exist.',
                $item->getId(),
                $this->getId()
            ));
        }
        $this->items[$this->getPosition($id)] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $id): CollectionInterface
    {
        if (!$this->has($id)) {
            throw new \DomainException(sprintf(
                'Failed removing "%s" in "%s" collection: item does not exist.',
                $id,
                $this->getId()
            ));
        }
        unset($this->items[$this->getPosition($id)]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): ItemInterface
    {
        if (!$this->has($id)) {
            throw new \DomainException(sprintf(
                'Failed getting "%s" in "%s" collection: item does not exist.',
                $id,
                $this->getId()
            ));
        }

        return $this->items[$this->getPosition($id)];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(string $id): int
    {
        $result = $this->searchItem($id);
        $position = key($result);
        if (!is_int($position)) {
            throw new \DomainException(sprintf(
                'Failed getting position of "%s" in "%s" collection: item does not exist.',
                $id,
                $this->getId()
            ));
        }

        return $position;
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
        if (count($this->items) < 1) {
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
        if (count($this->items) < 1) {
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
        return count($this->items);
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
        return sprintf("%s\n", json_encode($this->items));
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
    public function usort(\Closure $callback = null): CollectionInterface
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
     * Implements ArrayAccess.
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetSet($offset, $value): void
    {
        $this->add($value);
    }

    /**
     * Implements ArrayAccess.
     */
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
