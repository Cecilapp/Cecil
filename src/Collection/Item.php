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
 * Class Item.
 */
class Item implements ItemInterface
{
    /**
     * Item's identifier.
     *
     * @var string
     */
    protected $id;

    /**
     * Item's properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->setId($id);
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
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->properties;
    }
}
