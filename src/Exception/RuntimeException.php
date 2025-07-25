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
 * RuntimeException class.
 *
 * This class extends the built-in RuntimeException and implements the ExceptionInterface.
 * It is used to handle runtime exceptions in the Cecil application.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, string $file = '', int $line = 0)
    {
        $this->file = $file;
        $this->line = $line;

        parent::__construct($message, $code, $previous);
    }
}
