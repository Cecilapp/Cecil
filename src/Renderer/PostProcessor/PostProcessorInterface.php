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

namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

/**
 * PostProcessor interface.
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
