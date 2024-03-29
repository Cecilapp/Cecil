<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

/**
 * Interface PostProcessorInterface.
 */
interface PostProcessorInterface
{
    /**
     * Gives the Builder to the object.
     */
    public function __construct(\Cecil\Builder $builder);

    /**
     * Process output.
     */
    public function process(Page $page, string $output, string $format): string;
}
