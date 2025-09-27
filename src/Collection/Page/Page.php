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

namespace Cecil\Collection\Page;

use Cecil\Collection\Item;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Cocur\Slugify\Slugify;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Page class.
 *
 * Represents a page in the collection, which can be created from a file or be virtual.
 * Provides methods to manage page properties, variables, and rendering.
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

    /** @var array Output, by format. */
    protected $rendered = [];

    /** @var Collection pages list. */
    protected $pages;

    /** @var array */
    protected $paginator = [];

    /** @var \Cecil\Collection\Taxonomy\Vocabulary Terms of a vocabulary. */
    protected $terms;

    /** @var Slugify */
    private static $slugifier;

    public function __construct(mixed $id)
    {
        if (!\is_string($id) && !$id instanceof SplFileInfo) {
            throw new RuntimeException('Create a page with a string ID or a SplFileInfo.');
        }

        // set default properties and variables
        $this->setVirtual(true);
        $this->setType(Type::PAGE->value);
        $this->setVariables([
            'title'            => 'Page Title',
            'date'             => new \DateTime(),
            'updated'          => new \DateTime(),
            'weight'           => null,
            'filepath'         => null,
            'published'        => true,
            'content_template' => 'page.content.twig',
        ]);

        // if file, create ID from its pathname
        if ($id instanceof SplFileInfo) {
            $file = $id;
            $this->setFile($file);
            $id = self::createIdFromFile($file);
        }

        parent::__construct($id);
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): self
    {
        return parent::setId($id);
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

        $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
        $filename = $this->file->getFilenameWithoutExtension();
        // renames "README" to "index"
        $filename = strtolower(PrefixSuffix::sub($filename)) == 'readme' ? 'index' : $filename;

        // set properties
        $this->setVirtual(false);
        $this->setFolder($relativePath);
        $this->setSlug($filename);
        $this->setPath($this->getFolder() . '/' . $this->getSlug());
        // if "index" : type = section or homepage
        if (PrefixSuffix::sub($filename) == 'index') {
            // section by default
            $this->setType(Type::SECTION->value);
            // homepage
            if (empty($this->file->getRelativePath())) {
                $this->setType(Type::HOMEPAGE->value);
            }
        }
        // set variables
        $this->setVariables([
            'title'    => PrefixSuffix::sub($filename),
            'date'     => (new \DateTime())->setTimestamp($this->file->getMTime()),
            'updated'  => (new \DateTime())->setTimestamp($this->file->getMTime()),
            'filepath' => $this->file->getRelativePathname(),
        ]);
        // if prefix : set weight or date
        if (PrefixSuffix::hasPrefix($filename)) {
            $prefix = PrefixSuffix::getPrefix($filename);
            if ($prefix !== null) {
                // prefix is an integer: weight (used for sorting)
                if (is_numeric($prefix)) {
                    $this->setVariable('weight', (int) $prefix);
                }
                // prefix is a valid date?
                if (Util\Date::isValid($prefix)) {
                    $this->setVariable('date', (string) $prefix);
                }
            }
        }
        // if suffix : set language
        if (PrefixSuffix::hasSuffix($filename)) {
            $this->setVariable('language', PrefixSuffix::getSuffix($filename));
        }
        // set reference between page's translations (even if it exist in only one language)
        $this->setVariable('langref', $this->getPath());

        return $this;
    }

    /**
     * Returns file name, with extension.
     */
    public function getFileName(): ?string
    {
        if ($this->file === null) {
            return null;
        }

        return $this->file->getBasename();
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

        // homepage : path is empty
        if ($path == 'index') {
            $this->path = '';

            return $this;
        }

        // section/index : path = section
        if (substr($path, -6) == '/index') {
            $path = substr($path, 0, \strlen($path) - 6);
        }
        $this->path = $path;

        $lastslash = strrpos($this->path, '/');

        // top-level pages : slug = path
        if ($lastslash === false) {
            $this->slug = $this->path;

            return $this;
        }

        // set section
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
     * Set pages list.
     */
    public function setPages(Collection $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * Get pages list.
     */
    public function getPages(): ?Collection
    {
        return $this->pages;
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
                    throw new \Exception(\sprintf('The value of "%s" is not a valid date: "%s".', $name, var_export($value, true)));
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
                    throw new RuntimeException(\sprintf('"%s" variable should be "%s" (not "%s") in "%s".', $name, $slugify, (string) $value, $this->getId()));
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
     * Creates a page ID from a file (based on path).
     */
    private static function createIdFromFile(SplFileInfo $file): string
    {
        $relativePath = self::slugify(str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePath()));
        $filename = self::slugify(PrefixSuffix::subPrefix($file->getFilenameWithoutExtension()));
        // if file is "README.md", ID is "index"
        $filename = strtolower($filename) == 'readme' ? 'index' : $filename;
        // if file is section's index (ie: if file is "section/index.md", ID is "section")
        if (!empty($relativePath) && PrefixSuffix::sub($filename) == 'index') {
            // localized section (ie: if file is "section/index.fr.md", ID is "fr/section")
            if (PrefixSuffix::hasSuffix($filename)) {
                return PrefixSuffix::getSuffix($filename) . '/' . $relativePath;
            }

            return $relativePath;
        }
        // localized page (ie: if file is "page.fr.md", ID is "fr/page")
        if (PrefixSuffix::hasSuffix($filename)) {
            return trim(Util::joinPath(PrefixSuffix::getSuffix($filename), $relativePath, PrefixSuffix::sub($filename)), '/');
        }

        return trim(Util::joinPath($relativePath, $filename), '/');
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
}
