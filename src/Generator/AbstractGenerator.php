<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Config as Config;

/**
 * Abstract class AbstractGenerator.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /* @var Config */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }
}
