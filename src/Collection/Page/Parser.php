<?php declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Exception\RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Parser.
 */
class Parser
{
    // https://regex101.com/r/xH7cL3/2
    const PATTERN = '^\s*(?:<!--|---|\+\+\+){1}[\n\r\s]*(.*?)[\n\r\s]*(?:-->|---|\+\+\+){1}[\s\n\r]*(.*)$';

    /** @var SplFileInfo */
    protected $file;

    /** @var string */
    protected $frontmatter;

    /** @var string */
    protected $body;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * Parse the contents of the file.
     *
     * Example:
     * ---
     * title: Title
     * date: 2016-07-29
     * ---
     * Lorem Ipsum.
     *
     * @throws RuntimeException
     */
    public function parse(): self
    {
        if ($this->file->isFile()) {
            if (!$this->file->isReadable()) {
                throw new RuntimeException('Cannot read file');
            }
            preg_match(
                '/'.self::PATTERN.'/s',
                $this->file->getContents(),
                $matches
            );
            // if there is not front matter, set body only
            if (empty($matches)) {
                $this->body = $this->file->getContents();

                return $this;
            }
            $this->frontmatter = trim($matches[1]);
            $this->body = trim($matches[2]);
        }

        return $this;
    }

    /**
     * Get frontmatter.
     */
    public function getFrontmatter(): ?string
    {
        return $this->frontmatter;
    }

    /**
     * Get body.
     */
    public function getBody(): string
    {
        return $this->body;
    }
}
