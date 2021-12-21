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
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
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
    public function offsetGet($offset): mixed
    {
        return $this->properties[$offset];
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetUnset(mixed $offset): void
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
