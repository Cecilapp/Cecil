<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PageCollection;

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
     * @param PageCollection $pageCollection
     * @param \Closure       $messageCallback
     *
     * @return PageCollection
     */
    public function generate(PageCollection $pageCollection, \Closure $messageCallback);
}
