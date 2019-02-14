<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
    /**
     * Collection's identifier.
     *
     * @var string
     */
    protected $id = '';

    /**
     * Collection's items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Collection constructor.
     *
     * @param string|null $id
     * @param array       $items
     */
    public function __construct($id = null, $items = [])
    {
        $this->setId($id);
        $this->items = $items;
        /*
        if ($items) {
            foreach ($items as $item) {
                $this->add($item);
            }
        }*/
    }

    /**
     * If parameter is empty uses the object's hash.
     * {@inheritdoc}
     */
    public function setId(string $id = null)
    {
        $this->id = $id;
        if (empty($this->id)) {
            $this->id = spl_object_hash($this);
        }

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
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function add(ItemInterface $item): ?CollectionInterface
    {
        if ($this->has($item->getId())) {
            throw new \DomainException(sprintf(
                'Failed adding "%s" in "%s" collection: item already exists.',
                $item->getId(),
                $this->getId()
            ));
        }
        $this->items[$item->getId()] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $id, ItemInterface $item): ?CollectionInterface
    {
        if (!$this->has($id)) {
            throw new \DomainException(sprintf(
                'Failed replacing "%s" in "%s" collection: item does not exist.',
                $item->getId(),
                $this->getId()
            ));
        }
        $this->items[$id] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $id): ?CollectionInterface
    {
        if (!$this->has($id)) {
            throw new \DomainException(sprintf(
                'Failed removing "%s" in "%s" collection: item does not exist.',
                $id,
                $this->getId()
            ));
        }
        unset($this->items[$id]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): ?ItemInterface
    {
        if (!$this->has($id)) {
            return false;
        }

        return $this->items[$id];
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
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(\Closure $callback = null): CollectionInterface
    {
        $callback ? uasort($this->items, $callback) : uasort($this->items, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static(self::getId(), $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $callback): CollectionInterface
    {
        return new static(self::getId(), array_filter($this->items, $callback));
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $callback): CollectionInterface
    {
        return new static(self::getId(), array_map($callback, $this->items));
    }

    /**
     * Implement ArrayAccess.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param string $offset
     *
     * @return CollectionInterface|bool
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed         $offset
     * @param ItemInterface $value
     *
     * @return CollectionInterface|null
     */
    public function offsetSet($offset, $value)
    {
        return $this->add($value);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param string $offset
     *
     * @return CollectionInterface|null
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("%s\n", json_encode($this->items));
    }
}
