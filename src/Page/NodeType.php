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
 * Class NodeType.
 *
 * @method static NodeType HOMEPAGE()
 * @method static NodeType SECTION()
 * @method static NodeType TAXONOMY()
 * @method static NodeType TERMS()
 */
class NodeType extends Enum
{
    const HOMEPAGE = 'homepage';
    const SECTION = 'section';
    const TAXONOMY = 'taxonomy';
    const TERMS = 'terms';
}
