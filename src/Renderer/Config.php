<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;

/**
 * Class Config.
 */
class Config implements \ArrayAccess
{
    /** @var Builder Builder object. */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /** @var string Current language. */
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
    }
}
