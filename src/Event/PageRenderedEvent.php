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
 * Page rendered event.
 *
 * The rendered output of the page, in the given format, can be modified.
 */
class PageRenderedEvent extends PageEvent
{
    public function __construct(BuildContextInterface $builder, Page $page, protected string $format, protected string $output)
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
     * Returns the rendered output.
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Replaces the rendered output.
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;
    }
}
