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

namespace Cecil\Renderer;

use Cecil\Builder;

/**
 * Config renderer class.
 *
 * This class implements the \ArrayAccess interface to allow access to configuration
 * values using array syntax. It retrieves configuration values from the Builder's
 * configuration object, allowing for easy access to configuration settings in a
 * language-specific context.
 */
class Config implements \ArrayAccess
{
    /**
     * Builder object.
     * @var Builder
     */
    protected $builder;
    /**
     * Configuration object.
     * @var \Cecil\Config
     */
    protected $config;
    /**
     * Current language code.
     * @var string
     */
    protected $language;

    public function __construct(Builder $builder, string $language)
    {
        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
        $this->language = $language;
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return $this->config->has($offset);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->config->get($offset, $this->language);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
    }
}
