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

namespace Cecil\Exception;

class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, string $file = '', int $line = 0)
    {
        $this->file = $file;
        $this->line = $line;

        parent::__construct($message, $code, $previous);
    }
}
