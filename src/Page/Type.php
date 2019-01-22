<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Page;

use MyCLabs\Enum\Enum;

/**
 * Type enum.
 *
 * @method static Type PAGE()
 * @method static Type HOMEPAGE()
 * @method static Type SECTION()
 * @method static Type TAXONOMY()
 * @method static Type TERMS()
 */
class Type extends Enum
{
    const PAGE = 'page';
    const HOMEPAGE = 'homepage';
    const SECTION = 'section';
    const TAXONOMY = 'taxonomy';
    const TERMS = 'terms';
}
