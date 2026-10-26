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

namespace Cecil\Plugin;

use Cecil\BuildContextInterface;

/**
 * Plugin abstract class.
 */
abstract class AbstractPlugin implements PluginInterface
{
    /** @var BuildContextInterface */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    public function __construct(BuildContextInterface $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }
}
