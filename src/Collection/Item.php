<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
     * Item properties.
     *
     * @var array
     */
    protected $properties = [];

    /**
     * AbstractItem constructor.
     *
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->setId($id);
    }

    /**
     * If parameter is empty uses the object hash
     * {@inheritdoc}
     */
    public function setId($id = '')
    {
        if (empty($id)) {
            $id = spl_object_hash($this);
        }
        $this->offsetSet('id', $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->offsetGet('id');
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
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}
