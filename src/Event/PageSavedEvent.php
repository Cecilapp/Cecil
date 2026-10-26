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

namespace Cecil\Event;

use Cecil\BuildContextInterface;
use Cecil\Collection\Page\Page;

/**
 * Page saved event.
 */
class PageSavedEvent extends PageEvent
{
    public function __construct(BuildContextInterface $builder, Page $page, protected string $format, protected string $pathname)
    {
        parent::__construct($builder, $page);
    }

    /**
     * Returns the output format name (e.g.: "html").
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Returns the path of the saved file.
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }
}
