<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * Give config to object.
     *
     * @param \Cecil\Config $config
     */
    public function __construct(\Cecil\Config $config);

    /**
     * @param \Cecil\Collection\Collection $pageCollection
     * @param \Closure                     $messageCallback
     *
     * @return \Cecil\Collection\Collection
     */
    public function generate(\Cecil\Collection\Collection $pageCollection, \Closure $messageCallback);
}
