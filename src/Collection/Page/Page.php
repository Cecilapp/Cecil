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

namespace Cecil\Collection\Page;

use Cecil\Collection\Item;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Cocur\Slugify\Slugify;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Page.
 */
class Page extends Item
{
    const SLUGIFY_PATTERN = '/(^\/|[^._a-z0-9\/]|-)+/'; // should be '/^\/|[^_a-z0-9\/]+/'

    /** @var bool True if page is not created from a Markdown file. */
    protected $virtual;

    /** @var SplFileInfo */
    protected $file;

    /** @var string Homepage, Page, Section, etc. */
    protected $type;

    /** @var string */
    protected $folder;

    /** @var string */
    protected $slug;

    /** @var string folder + slug. */
    protected $path;

    /** @var string */
    protected $section;

    /** @var string */
    protected $frontmatter;

    /** @var string Body before conversion. */
    protected $body;

    /** @var array Front matter before conversion. */
    protected $fmVariables = [];

    /** @var string Body after Markdown conversion. */
    protected $html;

    /** @var Slugify */
    private static $slugifier;

    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setVirtual(true);
        $this->setType(Type::PAGE);
        // default variables
        $this->setVariables([
            'title'            => 'Page Title',
            'date'             => new \DateTime(),
            'updated'          => new \DateTime(),
            'weight'           => null,
            'filepath'         => null,
            'published'        => true,
            'content_template' => 'page.content.twig',
        ]);
    }

    /**
     * Turns a path (string) into a slug (URI).
     */
    public static function slugify(string $path): string
    {
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => self::SLUGIFY_PATTERN]);
        }

        return self::$slugifier->slugify($path);
    }

    /**
     * Creates the ID from the file path.
     */
    public static function createId(SplFileInfo $file): string
    {
        $relativepath = self::slugify(str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePath()));
        $basename = self::slugify(PrefixSuffix::subPrefix($file->getBasename('.'.$file->getExtension())));
        // case of "README" -> index
        $basename = (string) str_ireplace('readme', 'index', $basename);
        // case of section's index: "section/index" -> "section"
        if (!empty($relativepath) && PrefixSuffix::sub($basename) == 'index') {
            // case of a localized section
            if (PrefixSuffix::hasSuffix($basename)) {
                return $relativepath.'.'.PrefixSuffix::getSuffix($basename);
            }

            return $relativepath;
        }

        return trim(Util::joinPath($relativepath, $basename), '/');
    }

    /**
     * Returns the ID of a page without language suffix.
     */
    public function getIdWithoutLang(): string
    {
        return PrefixSuffix::sub($this->getId());
    }

    /**
     * Set file.
     */
    public function setFile(SplFileInfo $file): self
    {
        $this->setVirtual(false);
        $this->file = $file;

        /*
         * File path components
         */
        $fileRelativePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
        $fileExtension = $this->file->getExtension();
        $fileName = $this->file->getBasename('.'.$fileExtension);
        // case of "README" -> "index"
        $fileName = (string) str_ireplace('readme', 'index', $fileName);
        // case of "index" = home page
        if (empty($this->file->getRelativePath()) && PrefixSuffix::sub($fileName) == 'index') {
            $this->setType(Type::HOMEPAGE);
        }
        /*
         * Set protected variables
         */
        $this->setFolder($fileRelativePath); // ie: "blog"
        $this->setSlug($fileName); // ie: "post-1"
        $this->setPath($this->getFolder().'/'.$this->getSlug()); // ie: "blog/post-1"
        /*
         * Set default variables
         */
        $this->setVariables([
            'title'    => PrefixSuffix::sub($fileName),
            'date'     => (new \DateTime())->setTimestamp($this->file->getCTime()),
            'updated'  => (new \DateTime())->setTimestamp($this->file->getMTime()),
            'filepath' => $this->file->getRelativePathname(),
        ]);
        /*
         * Set specific variables
         */
        // is file has a prefix?
        if (PrefixSuffix::hasPrefix($fileName)) {
            $prefix = PrefixSuffix::getPrefix($fileName);
            if ($prefix !== null) {
                // prefix is a valid date?
                if (Util\Date::isDateValid($prefix)) {
                    $this->setVariable('date', (string) $prefix);
                } else {
                    // prefix is an integer: used for sorting
                    $this->setVariable('weight', (int) $prefix);
                }
            }
        }
        // is file has a language suffix?
        if (PrefixSuffix::hasSuffix($fileName)) {
            $this->setVariable('language', PrefixSuffix::getSuffix($fileName));
        }
        // set reference between translations
        $this->setVariable('langref', $this->getPath());

        return $this;
    }

    /**
     * Returns file real path.
     */
    public function getFilePath(): ?string
    {
        return $this->file->getRealPath() === false ? null : $this->file->getRealPath();
    }

    /**
     * Parse file content.
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
     * Get frontmatter.
     */
    public function getFrontmatter(): ?string
    {
        return $this->frontmatter;
    }

    /**
     * Get body as raw.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Set virtual status.
     */
    public function setVirtual(bool $virtual): self
    {
        $this->virtual = $virtual;

        return $this;
    }

    /**
     * Is current page is virtual?
     */
    public function isVirtual(): bool
    {
        return $this->virtual;
    }

    /**
     * Set page type.
     */
    public function setType(string $type): self
    {
        $this->type = new Type($type);

        return $this;
    }

    /**
     * Get page type.
     */
    public function getType(): string
    {
        return (string) $this->type;
    }

    /**
     * Set path without slug.
     */
    public function setFolder(string $folder): self
    {
        $this->folder = self::slugify($folder);

        return $this;
    }

    /**
     * Get path without slug.
     */
    public function getFolder(): ?string
    {
        return $this->folder;
    }

    /**
     * Set slug.
     */
    public function setSlug(string $slug): self
    {
        if (!$this->slug) {
            $slug = self::slugify(PrefixSuffix::sub($slug));
        }
        // force slug and update path
        if ($this->slug && $this->slug != $slug) {
            $this->setPath($this->getFolder().'/'.$slug);
        }
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set path.
     */
    public function setPath(string $path): self
    {
        $path = self::slugify(PrefixSuffix::sub($path));

        // case of homepage
        if ($path == 'index') {
            $this->path = '';

            return $this;
        }

        // case of custom sections' index (ie: content/section/index.md)
        if (substr($path, -6) == '/index') {
            $path = substr($path, 0, strlen($path) - 6);
        }
        $this->path = $path;

        // case of root pages
        $lastslash = strrpos($this->path, '/');
        if ($lastslash === false) {
            $this->slug = $this->path;

            return $this;
        }

        if (!$this->virtual && $this->getSection() === null) {
            $this->section = explode('/', $this->path)[0];
        }
        $this->folder = substr($this->path, 0, $lastslash);
        $this->slug = substr($this->path, -(strlen($this->path) - $lastslash - 1));

        return $this;
    }

    /**
     * Get path.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @see getPath()
     */
    public function getPathname(): ?string
    {
        return $this->getPath();
    }

    /**
     * Set section.
     */
    public function setSection(string $section): self
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get section.
     */
    public function getSection(): ?string
    {
        return !empty($this->section) ? $this->section : null;
    }

    /**
     * Set body as HTML.
     */
    public function setBodyHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get body as HTML.
     */
    public function getBodyHtml(): ?string
    {
        return $this->html;
    }

    /**
     * @see getBodyHtml()
     */
    public function getContent(): ?string
    {
        return $this->getBodyHtml();
    }

    /*
     * Helpers to set and get variables.
     */

    /**
     * Set an array as variables.
     *
     * @throws RuntimeException
     */
    public function setVariables(array $variables): self
    {
        foreach ($variables as $key => $value) {
            $this->setVariable($key, $value);
        }

        return $this;
    }

    /**
     * Get all variables.
     */
    public function getVariables(): array
    {
        return $this->properties;
    }

    /**
     * Set a variable.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws RuntimeException
     */
    public function setVariable(string $name, $value): self
    {
        // cast some strings to boolean
        $this->filterBool($value);
        if (is_array($value)) {
            array_walk_recursive($value, [$this, 'filterBool']);
        }
        // behavior for specific named variables
        switch ($name) {
            /**
             * date: 2012-10-08.
             */
            case 'date':
                try {
                    $date = Util\Date::dateToDatetime($value);
                } catch (\Exception $e) {
                    throw new RuntimeException(\sprintf('Expected date format for "date" in "%s" must be "YYYY-MM-DD" instead of "%s"', $this->getId(), (string) $value));
                }
                $this->offsetSet('date', $date);
                break;
            /**
             * schedule:
             *   publish: 2012-10-08
             *   expiry: 2012-10-09.
             */
            case 'schedule':
                $this->offsetSet('published', false);
                if (is_array($value)) {
                    if (array_key_exists('publish', $value) && Util\Date::dateToDatetime($value['publish']) <= Util\Date::dateToDatetime('now')) {
                        $this->offsetSet('published', true);
                    }
                    if (array_key_exists('expiry', $value) && Util\Date::dateToDatetime($value['expiry']) >= Util\Date::dateToDatetime('now')) {
                        $this->offsetSet('published', true);
                    }
                }
                break;
            /**
             * draft: true.
             */
            case 'draft':
                if ($value === true) {
                    $this->offsetSet('published', 0);
                }
                break;
            /**
             * path: about/about
             * slug: about.
             */
            case 'path':
            case 'slug':
                $slugify = self::slugify((string) $value);
                if ($value != $slugify) {
                    throw new RuntimeException(\sprintf('"%s" variable should be "%s" (not "%s") in "%s"', $name, $slugify, (string) $value, $this->getId()));
                }
                /** @see setPath() */
                /** @see setSlug() */
                $method = 'set'.\ucfirst($name);
                $this->$method($value);
                break;
            default:
                $this->offsetSet($name, $value);
        }

        return $this;
    }

    /**
     * Is variable exists?
     */
    public function hasVariable(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * Get a variable.
     *
     * @return mixed|null
     */
    public function getVariable(string $name)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }
    }

    /**
     * Unset a variable.
     */
    public function unVariable(string $name): self
    {
        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
        }

        return $this;
    }

    /**
     * Set front matter (only) variables.
     */
    public function setFmVariables(array $variables): self
    {
        $this->fmVariables = $variables;

        return $this;
    }

    /**
     * Get front matter variables.
     */
    public function getFmVariables(): array
    {
        return $this->fmVariables;
    }

    /**
     * Filter 'true', 'false', 'on', 'off', 'yes', 'no' to boolean.
     */
    private function filterBool(&$value)
    {
        if (is_string($value)) {
            if (in_array($value, ['true', 'on', 'yes'])) {
                $value = true;
            }
            if (in_array($value, ['false', 'off', 'no'])) {
                $value = false;
            }
        }
    }
}
