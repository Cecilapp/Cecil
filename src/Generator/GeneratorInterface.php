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

namespace Cecil\Generator;

/**
 * Generator interface.
 */
interface GeneratorInterface
{
    /**
     * Gives the Builder to the object.
     */
    public function __construct(\Cecil\Builder $builder);

    /**
     * Creates pages and adds it to collection.
     *
     * Use `$this->generatedPages->add($page);`
     */
    public function generate(): void;
}
