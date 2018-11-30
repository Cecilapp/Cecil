<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Page;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Parser.
 */
class Parser
{
    // https://regex101.com/r/xH7cL3/1
    const PATTERN = '^\s*(?:<!--|---|\+++){1}[\n\r\s]*(.*?)[\n\r\s]*(?:-->|---|\+++){1}[\s\n\r]*(.*)$';
    /**
     * @var SplFileInfo
     */
    protected $file;
    /**
     * @var string
     */
    protected $frontmatter;
    /**
     * @var string
     */
    protected $body;

    /**
     * Constructor.
     *
     * @param SplFileInfo $file
     */
    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * Parse the contents of the file.
     *
     * Example:
     *
     *     ---
     *     title: Title
     *     date: 2016-07-29
     *     ---
     *     Lorem Ipsum.
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function parse()
    {
        if ($this->file->isFile()) {
            if (!$this->file->isReadable()) {
                throw new \RuntimeException('Cannot read file');
            }
            // parse front matter
            preg_match(
                '/'.self::PATTERN.'/s',
                $this->file->getContents(),
                $matches
            );
            // if not front matter, set body only
            if (!$matches) {
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
     *
     * @return string
     */
    public function getFrontmatter()
    {
        return $this->frontmatter;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
