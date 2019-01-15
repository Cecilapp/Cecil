<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;

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
     * @param PagesCollection $PagesCollection
     * @param \Closure        $messageCallback
     *
     * @return PagesCollection
     */
    public function generate(PagesCollection $PagesCollection, \Closure $messageCallback);
}
