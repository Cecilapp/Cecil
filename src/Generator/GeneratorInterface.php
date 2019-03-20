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
     * Create page and add it to collection.
     *
     * Use `$this->generatedPages->add($page);`
     */
    public function generate(): void;
}
