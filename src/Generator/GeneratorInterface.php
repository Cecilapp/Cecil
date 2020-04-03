<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
     * Gives the Builder to the object.
     *
     * @param \Cecil\Builder $builder
     */
    public function __construct(\Cecil\Builder $builder);

    /**
     * Creates page and adds it to collection.
     *
     * Use `$this->generatedPages->add($page);`
     */
    public function generate(): void;
}
