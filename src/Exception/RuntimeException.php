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

    public function __construct(string $message, string $pageFile = null, int $pageLine = null, int $pageCol = null, \Throwable $previous = null)
    {
        $this->pageFile = $pageFile;
        $this->pageLine = $pageLine;
        $this->pageCol = $pageCol;

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
