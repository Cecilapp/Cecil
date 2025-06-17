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
 * ConfigException class.
 *
 * This class extends the built-in RuntimeException and implements the ExceptionInterface.
 * It is used to handle configuration-related exceptions in the Cecil application.
 */
class ConfigException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("Configuration: $message", $code, $previous);
    }
}
