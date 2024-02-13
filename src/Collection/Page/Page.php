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
    public const SLUGIFY_PATTERN = '/(^\/|[^._a-z0-9\/]|-)+/'; // should be '/^\/|[^_a-z0-9\/]+/'

    /** @var bool True if page is not created from a file. */
    protected $virtual;

    /** @var SplFileInfo */
    protected $file;

    /** @var Type Type */
    protected $type;

    /** @var string */
    protected $folder;

    /** @var string */
    protected $slug;

    /** @var string path = folder + slug. */
    protected $path;

    /** @var string */
    protected $section;

    /** @var string */
    protected $frontmatter;

    /** @var array Front matter before conversion. */
    protected $fmVariables = [];

    /** @var string Body before conversion. */
    protected $body;

    /** @var string Body after conversion. */
    protected $html;

    /** @var array Output, by format */
    protected $rendered = [];

    /** @var Collection Subpages of a list page. */
    protected $subPages;

    /** @var array */
    protected $paginator = [];

    /** @var \Cecil\Collection\Taxonomy\Vocabulary Terms of a vocabulary. */
    protected $terms;

    /** @var self Parent page of a PAGE page or a SECTION page */
    protected $parent;

    /** @var Slugify */
    private static $slugifier;

    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setVirtual(true);
        $this->setType(Type::PAGE->value);
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
     * toString magic method to prevent Twig get_attribute fatal error.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getId();
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
     * Creates the ID from the file (path).
     */
    public static function createIdFromFile(SplFileInfo $file): string
    {
        $fileComponents = self::getFileComponents($file);

        $fileComponents['path'];
        $fileComponents['name'];
        $fileComponents['ext'];


        $relativePath = self::slugify($fileComponents['path']);
        $basename = self::slugify(PrefixSuffix::subPrefix($file->getBasename('.' . $file->getExtension())));
        // if file is "README.md", ID is "index"
        $basename = (string) str_ireplace('readme', 'index', $basename);
        // if file is section's index: "section/index.md", ID is "section"
        if (!empty($relativePath) && PrefixSuffix::sub($basename) == 'index') {
            // case of a localized section's index: "section/index.fr.md", ID is "fr/section"
            if (PrefixSuffix::hasSuffix($basename)) {
                return PrefixSuffix::getSuffix($basename) . '/' . $relativePath;
            }

            return $relativePath;
        }
        // localized page
        if (PrefixSuffix::hasSuffix($basename)) {
            return trim(Util::joinPath(PrefixSuffix::getSuffix($basename), $relativePath, PrefixSuffix::sub($basename)), '/');
        }

        return trim(Util::joinPath($relativePath, $basename), '/');
    }

    /**
     * Returns the ID of a page without language.
     */
    public function getIdWithoutLang(): string
    {
        $langPrefix = $this->getVariable('language') . '/';
        if ($this->hasVariable('language') && Util\Str::startsWith($this->getId(), $langPrefix)) {
            return substr($this->getId(), \strlen($langPrefix));
        }

        return $this->getId();
    }

    /**
     * Set file.
     */
    public function setFile(SplFileInfo $file): self
    {
        $this->file = $file;
        $this->setVirtual(false);

        /*
         * File path components
         */
        $fileRelativePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
        $fileExtension = $this->file->getExtension();
        $fileName = $this->file->getBasename('.' . $fileExtension);
        // renames "README" to "index"
        $fileName = (string) str_ireplace('readme', 'index', $fileName);
        // case of "index" = home page
        if (empty($this->file->getRelativePath()) && PrefixSuffix::sub($fileName) == 'index') {
            $this->setType(Type::HOMEPAGE->value);
        }
        /*
         * Set page properties and variables
         */
        $this->setFolder($fileRelativePath);
        $this->setSlug($fileName);
        $this->setPath($this->getFolder() . '/' . $this->getSlug());
        $this->setVariables([
            'title'    => PrefixSuffix::sub($fileName),
            'date'     => (new \DateTime())->setTimestamp($this->file->getMTime()),
            'updated'  => (new \DateTime())->setTimestamp($this->file->getMTime()),
            'filepath' => $this->file->getRelativePathname(),
        ]);
        // is a section?
        if (PrefixSuffix::sub($fileName) == 'index') {
            $this->setType(Type::SECTION->value);
            $this->setVariable('title', ucfirst(explode('/', $fileRelativePath)[\count(explode('/', $fileRelativePath)) - 1]));
            // is the home page?
            if (empty($this->getFolder())) {
                $this->setType(Type::HOMEPAGE->value);
                $this->setVariable('title', 'Homepage');
            }
        }
        // is file has a prefix?
        if (PrefixSuffix::hasPrefix($fileName)) {
            $prefix = PrefixSuffix::getPrefix($fileName);
            if ($prefix !== null) {
                // prefix is a valid date?
                if (Util\Date::isValid($prefix)) {
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
        // set reference between page's translations, even if it exist in only one language
        $this->setVariable('langref', $this->getPath());

        return $this;
    }

    /**
     * Returns file real path.
     */
    public function getFilePath(): ?string
    {
        if ($this->file === null) {
            return null;
        }

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
     * Get front matter.
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
        $this->type = Type::from($type);

        return $this;
    }

    /**
     * Get page type.
     */
    public function getType(): string
    {
        return $this->type->value;
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
            $this->setPath($this->getFolder() . '/' . $slug);
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
        $path = trim($path, '/');
        // case of homepage
        if ($path == 'index') {
            $this->path = '';
            return $this;
        }
        // case of custom sections' index (ie: section/index.md -> section)
        if (substr($path, -6) == '/index') {
            $path = substr($path, 0, \strlen($path) - 6);
        }
        $this->path = $path;
        $lastslash = strrpos($this->path, '/');
        // case of root/top-level pages
        if ($lastslash === false) {
            $this->slug = $this->path;
            return $this;
        }
        // case of sections' pages: set section
        if (!$this->virtual && $this->getSection() === null) {
            $this->section = explode('/', $this->path)[0];
        }
        // set/update folder and slug
        $this->folder = substr($this->path, 0, $lastslash);
        $this->slug = substr($this->path, -(\strlen($this->path) - $lastslash - 1));
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
     * Unset section.
     */
    public function unSection(): self
    {
        $this->section = null;

        return $this;
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

    /**
     * Add rendered.
     */
    public function addRendered(array $rendered): self
    {
        $this->rendered += $rendered;

        return $this;
    }

    /**
     * Get rendered.
     */
    public function getRendered(): array
    {
        return $this->rendered;
    }

    /**
     * Set Subpages.
     */
    public function setPages(\Cecil\Collection\Page\Collection $subPages): self
    {
        $this->subPages = $subPages;

        return $this;
    }

    /**
     * Get Subpages.
     */
    public function getPages(): ?\Cecil\Collection\Page\Collection
    {
        return $this->subPages;
    }

    /**
     * Set paginator.
     */
    public function setPaginator(array $paginator): self
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * Get paginator.
     */
    public function getPaginator(): array
    {
        return $this->paginator;
    }

    /**
     * Paginator backward compatibility.
     */
    public function getPagination(): array
    {
        return $this->getPaginator();
    }

    /**
     * Set vocabulary terms.
     */
    public function setTerms(\Cecil\Collection\Taxonomy\Vocabulary $terms): self
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Get vocabulary terms.
     */
    public function getTerms(): \Cecil\Collection\Taxonomy\Vocabulary
    {
        return $this->terms;
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
     * @param string $name  Name of the variable
     * @param mixed  $value Value of the variable
     *
     * @throws RuntimeException
     */
    public function setVariable(string $name, $value): self
    {
        $this->filterBool($value);
        switch ($name) {
            case 'date':
            case 'updated':
            case 'lastmod':
                try {
                    $date = Util\Date::toDatetime($value);
                } catch (\Exception) {
                    throw new \Exception(sprintf('The value of "%s" is not a valid date: "%s".', $name, var_export($value, true)));
                }
                $this->offsetSet($name == 'lastmod' ? 'updated' : $name, $date);
                break;

            case 'schedule':
                /*
                 * publish: 2012-10-08
                 * expiry: 2012-10-09
                 */
                $this->offsetSet('published', false);
                if (\is_array($value)) {
                    if (\array_key_exists('publish', $value) && Util\Date::toDatetime($value['publish']) <= Util\Date::toDatetime('now')) {
                        $this->offsetSet('published', true);
                    }
                    if (\array_key_exists('expiry', $value) && Util\Date::toDatetime($value['expiry']) >= Util\Date::toDatetime('now')) {
                        $this->offsetSet('published', true);
                    }
                }
                break;
            case 'draft':
                // draft: true = published: false
                if ($value === true) {
                    $this->offsetSet('published', false);
                }
                break;
            case 'path':
            case 'slug':
                $slugify = self::slugify((string) $value);
                if ($value != $slugify) {
                    throw new RuntimeException(sprintf('"%s" variable should be "%s" (not "%s") in "%s".', $name, $slugify, (string) $value, $this->getId()));
                }
                $method = 'set' . ucfirst($name);
                $this->$method($value);
                break;
            default:
                $this->offsetSet($name, $value);
        }

        return $this;
    }

    /**
     * Is variable exists?
     *
     * @param string $name Name of the variable
     */
    public function hasVariable(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * Get a variable.
     *
     * @param string     $name    Name of the variable
     * @param mixed|null $default Default value
     *
     * @return mixed|null
     */
    public function getVariable(string $name, $default = null)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }

        return $default;
    }

    /**
     * Unset a variable.
     *
     * @param string $name Name of the variable
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
     * {@inheritdoc}
     */
    public function setId(string $id): self
    {
        return parent::setId($id);
    }

    /**
     * Set parent page.
     */
    public function setParent(self $page): self
    {
        //if ($page->getId() != $this->getId()) {
        $this->parent = $page;
        //}

        return $this;
    }

    /**
     * Returns parent page if exists.
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Returns array of ancestors pages.
     */
    public function getAncestors(): ?array
    {
        $parent = $this->getParent();
        $ancestors[] = $parent;
        do {
            $parent = $parent->getParent();
            $ancestors[] = $parent;
        } while ($parent !== null && !empty($parent->getParent()));

        return $ancestors;
    }

    /**
     * Cast "boolean" string (or array of strings) to boolean.
     *
     * @param mixed $value Value to filter
     *
     * @return bool|mixed
     *
     * @see strToBool()
     */
    private function filterBool(&$value)
    {
        \Cecil\Util\Str::strToBool($value);
        if (\is_array($value)) {
            array_walk_recursive($value, '\Cecil\Util\Str::strToBool');
        }
    }

    /**
     * Get file components.
     *
     * [
     *   path => relative path,
     *   name => name,
     *   ext  => extension,
     * ]
     */
    private static function getFileComponents(SplFileInfo $file): array
    {
        return [
            'path' => str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePath()),
            'name' => (string) str_ireplace('readme', 'index', $file->getBasename('.' . $file->getExtension())),
            'ext'  => $file->getExtension(),
        ];
    }
}
