<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Collection\Item;
use Cecil\Page\Parser;
use Cecil\Page\Prefix;
use Cecil\Page\Type;
use Cecil\Page\VariableTrait;
use Cecil\Util;
use Cocur\Slugify\Slugify;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Page.
 */
class Page extends Item
{
    use VariableTrait;

    const SLUGIFY_PATTERN = '/(^\/|[^a-z0-9\/]|-)+/';

    /**
     * @var SplFileInfo
     */
    protected $file;
    /**
     * @var string
     */
    protected $fileExtension;
    /**
     * @var string
     */
    protected $filePath;
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var string
     */
    protected $filePathname;
    /**
     * @var bool
     */
    protected $virtual = false;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var string
     */
    protected $id;
    /**
     * @var string
     */
    protected $pathname;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $frontmatter;
    /**
     * @var string
     */
    protected $body;
    /**
     * @var string
     */
    protected $html;

    /**
     * Constructor.
     *
     * @param SplFileInfo|null $file
     */
    public function __construct(SplFileInfo $file = null)
    {
        $this->file = $file;

        // physical page
        if ($this->file instanceof SplFileInfo) {
            /*
             * File path components
             */
            // ie: content/Blog/Post 1.md
            //             |    |      └─ fileExtension
            //             |    └─ fileName
            //             └─ filePath
            $this->fileExtension = pathinfo($this->file, PATHINFO_EXTENSION);
            $this->filePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
            $this->fileName = $this->file->getBasename('.'.$this->fileExtension);
            // filePathname = ilePath + '/' + fileName
            // ie: "Blog/Post 1"
            $this->filePathname = ($this->filePath ? $this->filePath.'/' : '')
                .($this->filePath && $this->fileName == 'index' ? '' : $this->fileName);
            /*
             * Set properties
             */
            // ID. ie: "blog/post-1"
            $this->id = $this->slugify(Prefix::subPrefix($this->filePathname));
            // Path. ie: "blog"
            $this->path = $this->slugify($this->filePath);
            // Name. ie: "post-1"
            $this->name = $this->slugify(Prefix::subPrefix($this->fileName));
            // Pathname. ie: "blog/post-1"
            $this->pathname = $this->slugify(Prefix::subPrefix($this->filePathname));
            /*
             * Set default values
             */
            // Section. ie: "blog"
            $this->setSection(explode('/', $this->path)[0]);
            /*
             * Set variables default values (overridden by front matter)
             */
            // title. ie: "Post 1"
            $this->setVariable('title', Prefix::subPrefix($this->fileName));
            // date (from file meta)
            $this->setVariable('date', filemtime($this->file->getPathname()));
            // url
            $this->setPermalink($this->pathname.'/');
            // special case: file has a prefix
            if (Prefix::hasPrefix($this->filePathname)) {
                // prefix is a valid date?
                if (Util::isValidDate(Prefix::getPrefix($this->filePathname))) {
                    $this->setVariable('date', (string) Prefix::getPrefix($this->filePathname));
                } else {
                    // prefix is an integer, use for sorting
                    $this->setVariable('weight', (int) Prefix::getPrefix($this->filePathname));
                }
            }

            parent::__construct($this->id);
        } else {
            // virtual page
            $this->virtual = true;

            parent::__construct();
        }
        $this->setType(Type::PAGE);
        $this->setVariable('virtual', $this->virtual);
        $this->setVariable('published', true);
        $this->setVariable('content_template', 'page.content.twig');
    }

    /**
     * Turn a path (string) into a slung (URL).
     *
     * @param string $string
     *
     * @return string
     */
    public static function slugify(string $string): string
    {
        return Slugify::create([
            'regexp' => self::SLUGIFY_PATTERN,
        ])->slugify($string);
    }

    /**
     * Is current page is virtual?
     *
     * @return bool
     */
    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    /**
     * Set page type.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = new Type($type);

        return $this;
    }

    /**
     * Get page type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Parse file content.
     *
     * @return self
     */
    public function parse(): self
    {
        $parser = new Parser($this->file);
        $parsed = $parser->parse();
        $this->frontmatter = $parsed->getFrontmatter();
        $this->body = $parsed->getBody();

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set path.
     *
     * @param $path
     *
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set path name.
     *
     * @param string $pathname
     *
     * @return self
     */
    public function setPathname(string $pathname): self
    {
        $this->pathname = $pathname;

        return $this;
    }

    /**
     * Get path name.
     *
     * @return string
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }

    /**
     * Set section.
     *
     * @param string $section
     *
     * @return self
     */
    public function setSection(string $section): self
    {
        $this->setVariable('section', $section);

        return $this;
    }

    /**
     * Get section.
     *
     * @return string|false
     */
    public function getSection(): ?string
    {
        if (empty($this->getVariable('section')) && !empty($this->path)) {
            $this->setSection(explode('/', $this->path)[0]);
        }

        return $this->getVariable('section');
    }

    /**
     * Set permalink.
     *
     * @param string $permalink
     *
     * @return self
     */
    public function setPermalink(string $permalink): self
    {
        $this->setVariable('permalink', $permalink);

        return $this;
    }

    /**
     * Get permalink.
     *
     * @return string|false
     */
    public function getPermalink(): ?string
    {
        if (empty($this->getVariable('permalink'))) {
            $this->setPermalink($this->getPathname().'/');
        }

        return $this->getVariable('permalink');
    }

    /**
     * Get frontmatter.
     *
     * @return string|null
     */
    public function getFrontmatter(): ?string
    {
        return $this->frontmatter;
    }

    /**
     * Get body as raw.
     *
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set body as HTML.
     *
     * @param string $html
     *
     * @return self
     */
    public function setBodyHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get body as HTML.
     *
     * @return string|null
     */
    public function getBodyHtml(): ?string
    {
        return $this->html;
    }

    /**
     * @see getBodyHtml()
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getBodyHtml();
    }

    /**
     * Set layout.
     *
     * @param string $layout
     *
     * @return self
     */
    public function setLayout(string $layout): self
    {
        $this->setVariable('layout', $layout);

        return $this;
    }

    /**
     * Get layout.
     *
     * @return string|false
     */
    public function getLayout(): ?string
    {
        return $this->getVariable('layout');
    }
}
