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
     * Give Builder to object.
     *
     * @param \Cecil\Builder $builder
     */
    public function __construct(\Cecil\Builder $builder);

    /**
     * Create page and add it to collection.
     *
     * Use `$this->generatedPages->add($page);`
     */
    public function generate(): void;
}
