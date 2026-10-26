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

/**
 * Build events names.
 *
 * Events are dispatched during the build process, and can be listened by event subscribers
 * declared in the `extensions.subscribers` configuration (or added with `Builder::getEventDispatcher()`).
 */
final class BuildEvents
{
    /**
     * Dispatched before the first step of the build.
     *
     * @Event("Cecil\Event\BuildEvent")
     */
    public const string BUILD_START = 'build.start';

    /**
     * Dispatched after the last step of the build.
     *
     * @Event("Cecil\Event\BuildEvent")
     */
    public const string BUILD_FINISHED = 'build.finished';

    /**
     * Dispatched before each processed step.
     *
     * @Event("Cecil\Event\StepEvent")
     */
    public const string STEP_PRE = 'step.pre';

    /**
     * Dispatched after each processed step.
     *
     * @Event("Cecil\Event\StepEvent")
     */
    public const string STEP_POST = 'step.post';

    /**
     * Dispatched after a page is created from a content file.
     *
     * @Event("Cecil\Event\PageEvent")
     */
    public const string PAGE_CREATED = 'page.created';

    /**
     * Dispatched after a page is converted (front matter and body).
     *
     * @Event("Cecil\Event\PageEvent")
     */
    public const string PAGE_CONVERTED = 'page.converted';

    /**
     * Dispatched after a page is rendered in an output format (after post-processors): the output can be modified.
     *
     * @Event("Cecil\Event\PageRenderedEvent")
     */
    public const string PAGE_RENDERED = 'page.rendered';

    /**
     * Dispatched after a rendered page is saved to an output file.
     *
     * @Event("Cecil\Event\PageSavedEvent")
     */
    public const string PAGE_SAVED = 'page.saved';
}
