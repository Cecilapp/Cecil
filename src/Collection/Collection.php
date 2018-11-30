<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Class Collection.
 */
class Collection implements CollectionInterface
{
    /**
     * Collections's identifier.
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
     * AbstractCollection constructor.
     *
     * @param string|null $id
     * @param array       $items
     */
    public function __construct($id = null, $items = [])
    {
        $this->setId($id);
        $this->items = $items;
    }

    /**
     * If parameter is empty uses the object hash
     * {@inheritdoc}
     */
    public function setId($id = '')
    {
        if (empty($id)) {
            $this->id = spl_object_hash($this);
        } else {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id)
    {
        return array_key_exists($id, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function add(ItemInterface $item)
    {
        if ($this->has($item->getId())) {
            throw new \DomainException(sprintf(
                'Failed adding item "%s": an item with that id has already been added.',
                $item->getId()
            ));
        }
        $this->items[$item->getId()] = $item;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($id, ItemInterface $item)
    {
        if ($this->has($id)) {
            $this->items[$id] = $item;
        } else {
            throw new \DomainException(sprintf(
                'Failed replacing item "%s": item does not exist.',
                $item->getId()
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        if ($this->has($id)) {
            unset($this->items[$id]);
        } else {
            throw new \DomainException(sprintf(
                'Failed removing item with ID "%s": item does not exist.',
                $id
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            return false;
        }

        return $this->items[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_keys($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(\Closure $callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : uasort($items, function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static(self::getId(), $items);
    }

    /**
     * Sort items by date.
     *
     * @return Collection
     */
    public function sortByDate()
    {
        return $this->usort(function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return Collection
     */
    public function filter(\Closure $callback)
    {
        return new static(self::getId(), array_filter($this->items, $callback));
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $callback)
    {
        return new static(self::getId(), array_map($callback, $this->items));
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
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
     * @param mixed $offset
     *
     * @return null|CollectionInterface
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->items)."\n";
    }
}
