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

namespace Cecil\Collection\Page;

/**
 * Enum Type.
 *
 * Defines the different types of pages in a collection.
 */
enum Type: string
{
    case PAGE = 'page';
    case HOMEPAGE = 'homepage';
    case SECTION = 'section';
    case VOCABULARY = 'vocabulary';
    case TERM = 'term';
}
