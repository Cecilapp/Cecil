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

namespace Cecil\Collection\Page;

/**
 * Enum Status.
 *
 * Defines the different statuses a page can have.
 */
enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case UNPUBLISHED = 'unpublished';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
}
