<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

/**
 * Abstract class AbstractGenerator.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /* @var \Cecil\Config */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(\Cecil\Config $config)
    {
        $this->config = $config;
    }
}
