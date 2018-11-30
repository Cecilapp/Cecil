<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Generator;

/**
 * Interface GeneratorInterface.
 */
interface GeneratorInterface
{
    /**
     * Give config to object.
     *
     * @param \PHPoole\Config $config
     */
    public function __construct(\PHPoole\Config $config);

    /**
     * @param \PHPoole\Collection\Collection $pageCollection
     * @param \Closure                       $messageCallback
     *
     * @return \PHPoole\Collection\Collection
     */
    public function generate(\PHPoole\Collection\Collection $pageCollection, \Closure $messageCallback);
}
