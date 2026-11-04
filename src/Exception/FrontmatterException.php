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

namespace Cecil\Exception;

/**
 * FrontmatterException class.
 *
 * This class extends the Cecil RuntimeException.
 * It is thrown when the front matter of a page does not match its schema.
 */
class FrontmatterException extends RuntimeException
{
}
