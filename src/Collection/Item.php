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
    /** @var string Item's identifier. */
    protected $id;

    /** @var array Item's properties. */
    protected $properties = [];

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
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetUnset($offset): void
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
