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
    private $pageFile;
    private $pageLine;
    private $pageCol;

    public function __construct(string $message, string|null $file = null, int|null $line = null, int|null $col = null, \Throwable|null $previous = null)
    {
        $this->pageFile = $file;
        $this->pageLine = $line;
        $this->pageCol = $col;

        parent::__construct($message, 0, $previous);
    }

    public function getPageFile(): ?string
    {
        return $this->pageFile;
    }

    public function getPageLine(): ?int
    {
        return $this->pageLine;
    }

    public function getPageCol(): ?int
    {
        return $this->pageCol;
    }
}
