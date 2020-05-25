<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Collection\Page;

use Cecil\Collection\Item;
use Cecil\Config;
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
    /** @var string folder + slug */
    protected $path;
    /** @var string */
    protected $section;
    /** @var string */
    protected $frontmatter;
    /** @var string Body before conversion. */
    protected $body;
    /** @var string Body after Markdown conversion. */
    protected $html;
    /** @var Slugify */
    private static $slugifier;

    /**
     * @param string $id
     */
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
     *
     * @param string $path
     *
     * @return string
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
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    public static function createId(SplFileInfo $file): string
    {
        $relativepath = self::slugify(str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePath()));
        $basename = self::slugify(PrefixSuffix::subPrefix($file->getBasename('.'.$file->getExtension())));
        // case of "README" -> index
        $basename = str_ireplace('readme', 'index', $basename);
        // case of section's index: "section/index" -> "section"
        if (!empty($relativepath) && $basename == 'index') {
            return $relativepath;
        }

        return trim(Util::joinPath($relativepath, $basename), '/');
    }

    /**
     * Set file.
     *
     * @param SplFileInfo $file
     *
     * @return self
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
        // case of "README" -> index
        $fileName = str_ireplace('readme', 'index', $fileName);
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
                if (Util::isDateValid($prefix)) {
                    $this->setVariable('date', (string) $prefix);
                } else {
                    // prefix is an integer: used for sorting
                    $this->setVariable('weight', (int) $prefix);
                }
            }
        }
        // is file has a suffix?
        if (PrefixSuffix::hasSuffix($fileName)) {
            $this->setVariable('language', PrefixSuffix::getSuffix($fileName));
        }
        $this->setVariable('langref', PrefixSuffix::sub($fileName));

        return $this;
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
     * Set virtual status.
     *
     * @param bool $virtual
     *
     * @return self
     */
    public function setVirtual(bool $virtual): self
    {
        $this->virtual = $virtual;

        return $this;
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
     * @return string
     */
    public function getType(): string
    {
        return (string) $this->type;
    }

    /**
     * Set path without slug.
     *
     * @param string $folder
     *
     * @return self
     */
    public function setFolder(string $folder): self
    {
        $this->folder = self::slugify($folder);

        return $this;
    }

    /**
     * Get path without slug.
     *
     * @return string|null
     */
    public function getFolder(): ?string
    {
        return $this->folder;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return self
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
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return self
     */
    public function setPath(string $path): self
    {
        $path = self::slugify(PrefixSuffix::sub($path));

        // special case: homepage
        if ($path == 'index') {
            $this->path = '';

            return $this;
        }

        // special case: custom section index (ie: content/section/index.md)
        if (substr($path, -6) == '/index') {
            $path = substr($path, 0, strlen($path) - 6);
        }
        $this->path = $path;

        // explode path by slash
        $lastslash = strrpos($this->path, '/');
        if ($lastslash === false) {
            $this->section = null;
            $this->folder = null;
            $this->slug = $this->path;

            return $this;
        }
        if (!$this->virtual) {
            $this->section = explode('/', $this->path)[0];
        }
        $this->folder = substr($this->path, 0, $lastslash);
        $this->slug = substr($this->path, -(strlen($this->path) - $lastslash - 1));

        return $this;
    }

    /**
     * Get path.
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @see getPath()
     *
     * @return string|null
     */
    public function getPathname(): ?string
    {
        return $this->getPath();
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
        $this->section = $section;

        return $this;
    }

    /**
     * Get section.
     *
     * @return string|null
     */
    public function getSection(): ?string
    {
        return $this->section;
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
     * Returns the path to the output (rendered) file.
     *
     * Use cases:
     * - default: path + suffix + extension (ie: blog/post-1/index.html)
     * - subpath: path + subpath + suffix + extension (ie: blog/post-1/amp/index.html)
     * - ugly: path + extension (ie: 404.html, sitemap.xml, robots.txt)
     * - path only (ie: _redirects)
     * - i18n: language + path + suffix + extension (ie: fr/blog/page/index.html)
     *
     * @param string      $format
     * @param Config|null $config
     *
     * @return string
     */
    public function getOutputFile(string $format, Config $config = null): string
    {
        $path = $this->getPath();
        $subpath = '';
        $suffix = '/index';
        $extension = 'html';
        $uglyurl = (bool) $this->getVariable('uglyurl');
        $language = $this->getVariable('language');

        // site config
        if ($config) {
            $subpath = (string) $config->getOutputFormatProperty($format, 'subpath');
            $suffix = (string) $config->getOutputFormatProperty($format, 'suffix');
            $extension = (string) $config->getOutputFormatProperty($format, 'extension');
        }

        // if ugly URL: not suffix
        if ($uglyurl) {
            $suffix = null;
        }
        // formatting strings
        if ($subpath) {
            $subpath = \sprintf('/%s', ltrim($subpath, '/'));
        }
        if ($suffix) {
            $suffix = \sprintf('/%s', ltrim($suffix, '/'));
        }
        if ($extension) {
            $extension = \sprintf('.%s', $extension);
        }
        if ($language !== null) {
            $language = \sprintf('%s/', $language);
        }
        // homepage special case: path = 'index'
        if (empty($path) && empty($suffix)) {
            $path = 'index';
        }

        return $language.$path.$subpath.$suffix.$extension;
    }

    /**
     * Returns the public URL.
     *
     * @param string      $format
     * @param Config|null $config
     *
     * @return string
     */
    public function getUrl(string $format = 'html', Config $config = null): string
    {
        $uglyurl = $this->getVariable('uglyurl') ? true : false;
        $output = $this->getOutputFile($format, $config);

        if (!$uglyurl) {
            $output = str_replace('index.html', '', $output);
        }

        return $output;
    }

    /*
     * Helpers to set and get variables.
     */

    /**
     * Set an array as variables.
     *
     * @param array $variables
     *
     * @throws \Exception
     *
     * @return self
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
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->properties;
    }

    /**
     * Set a variable.
     *
     * @param $name
     * @param $value
     *
     * @throws \Exception
     *
     * @return self
     */
    public function setVariable($name, $value): self
    {
        if (is_bool($value)) {
            $value = $value ?: 0;
        }
        switch ($name) {
            case 'date':
                try {
                    $date = Util::dateToDatetime($value);
                } catch (\Exception $e) {
                    throw new \Exception(sprintf(
                        'Expected date format (ie: "2012-10-08") for "date" in "%s" instead of "%s"',
                        $this->getId(),
                        $value
                    ));
                }
                $this->offsetSet('date', $date);
                break;
            case 'draft':
                if ($value === true) {
                    $this->offsetSet('published', false);
                }
                break;
            case 'path':
            case 'slug':
                $slugify = self::slugify($value);
                if ($value != $slugify) {
                    throw new \Exception(sprintf(
                        '"%s" variable should be "%s" (not "%s") in "%s"',
                        $name,
                        $slugify,
                        $value,
                        $this->getId()
                    ));
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
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasVariable(string $name): bool
    {
        return $this->offsetExists($name);
    }

    /**
     * Get a variable.
     *
     * @param string $name
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
     *
     * @param string $name
     *
     * @return self
     */
    public function unVariable(string $name): self
    {
        if ($this->offsetExists($name)) {
            $this->offsetUnset($name);
        }

        return $this;
    }
}
