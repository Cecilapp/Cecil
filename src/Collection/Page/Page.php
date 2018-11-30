<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Collection\Item;
use Cecil\Page\NodeType;
use Cecil\Page\Parser;
use Cecil\Page\VariableTrait;
use Cocur\Slugify\Slugify;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class Page.
 */
class Page extends Item
{
    use VariableTrait;

    const SLUGIFY_PATTERN = '/(^\/|[^a-z0-9\/]|-)+/';
    // https://regex101.com/r/tJWUrd/1
    const PREFIX_PATTERN = '^(.*?)(([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])|[0-9]+)(-|_|\.)(.*)$';

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
    protected $fileId;

    /**
     * @var bool
     */
    protected $virtual = false;
    /**
     * @var string
     */
    protected $nodeType;

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
     * @param null|SplFileInfo $file
     */
    public function __construct(SplFileInfo $file = null)
    {
        $this->file = $file;

        if ($this->file instanceof SplFileInfo) {
            // file extension: "md"
            $this->fileExtension = pathinfo($this->file, PATHINFO_EXTENSION);
            // file path: "Blog"
            $this->filePath = str_replace(DIRECTORY_SEPARATOR, '/', $this->file->getRelativePath());
            // file name: "Post 1"
            $this->fileName = basename($this->file->getBasename(), '.'.$this->fileExtension);
            // file id: "Blog/Post 1"
            $this->fileId = ($this->filePath ? $this->filePath.'/' : '')
                .($this->filePath && $this->fileName == 'index' ? '' : $this->fileName);
            /*
             * variables default values
             */
            // id - ie: "blog/post-1"
            $this->id = $this->urlize(self::subPrefix($this->fileId));
            // pathname - ie: "blog/post-1"
            $this->pathname = $this->urlize(self::subPrefix($this->fileId));
            // path - ie: "blog"
            $this->path = $this->urlize($this->filePath);
            // name - ie: "post-1"
            $this->name = $this->urlize(self::subPrefix($this->fileName));
            /*
             * front matter default values
             */
            // title - ie: "Post 1"
            $this->setTitle(self::subPrefix($this->fileName));
            // section - ie: "blog"
            $this->setSection(explode('/', $this->path)[0]);
            // date from file meta
            $this->setDate(filemtime($this->file->getPathname()));
            // file as a prefix?
            if (false !== self::getPrefix($this->fileId)) {
                // prefix is a valid date?
                $isValidDate = function ($date, $format = 'Y-m-d') {
                    $d = \DateTime::createFromFormat($format, $date);

                    return $d && $d->format($format) === $date;
                };
                if ($isValidDate(self::getPrefix($this->fileId))) {
                    $this->setDate(self::getPrefix($this->fileId));
                } else {
                    // prefix is an integer
                    $this->setWeight(self::getPrefix($this->fileId));
                }
            }
            // permalink
            $this->setPermalink($this->pathname);

            parent::__construct($this->id);
        } else {
            $this->virtual = true;

            parent::__construct();
        }
        $this->setVariable('virtual', $this->virtual);
        $this->setVariable('published', true);
        $this->setVariable('content_template', 'page.content.twig');
    }

    /**
     * Return matches array if prefix exist or false.
     *
     * @param $string
     *
     * @return string[]|false
     */
    public static function asPrefix($string)
    {
        if (preg_match('/'.self::PREFIX_PATTERN.'/', $string, $matches)) {
            return $matches;
        } else {
            return false;
        }
    }

    /**
     * Return prefix if prefix or false.
     *
     * @param $string
     *
     * @return string[]|false
     */
    public static function getPrefix($string)
    {
        if (false !== ($matches = self::asPrefix($string))) {
            return $matches[2];
        }

        return false;
    }

    /**
     * Return string without prefix (if exist).
     *
     * @param $string
     *
     * @return string
     */
    public static function subPrefix($string)
    {
        if (false !== ($matches = self::asPrefix($string))) {
            return $matches[1].$matches[7];
        }

        return $string;
    }

    /**
     * Format string into URL.
     *
     * @param $string
     *
     * @return string
     */
    public static function urlize($string)
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
    public function isVirtual()
    {
        return $this->virtual;
    }

    /**
     * Set node type.
     *
     * @param string $nodeType
     *
     * @return self
     */
    public function setNodeType($nodeType)
    {
        $this->nodeType = new NodeType($nodeType);

        return $this;
    }

    /**
     * Get node type.
     *
     * @return string
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * Parse file content.
     *
     * @return $this
     */
    public function parse()
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
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path.
     *
     * @param $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set path name.
     *
     * @param string $pathname
     *
     * @return $this
     */
    public function setPathname($pathname)
    {
        $this->pathname = $pathname;

        return $this;
    }

    /**
     * Get path name.
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->pathname;
    }

    /**
     * Set section.
     *
     * @param $section
     *
     * @return $this
     */
    public function setSection($section)
    {
        $this->setVariable('section', $section);

        return $this;
    }

    /**
     * Get section.
     *
     * @return mixed|false
     */
    public function getSection()
    {
        if (empty($this->getVariable('section')) && !empty($this->path)) {
            $this->setSection(explode('/', $this->path)[0]);
        }

        return $this->getVariable('section');
    }

    /**
     * Set title.
     *
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->setVariable('title', $title);

        return $this;
    }

    /**
     * Get title.
     *
     * @return mixed|false
     */
    public function getTitle()
    {
        return $this->getVariable('title');
    }

    /**
     * Set date.
     *
     * @param $date
     *
     * @return $this
     */
    public function setDate($date)
    {
        $this->setVariable('date', $date);

        return $this;
    }

    /**
     * Get Date.
     *
     * @return \DateTime|false
     */
    public function getDate()
    {
        return $this->getVariable('date');
    }

    /**
     * Set weight.
     *
     * @param $int
     *
     * @return $this
     */
    public function setWeight($int)
    {
        $this->setVariable('weight', $int);

        return $this;
    }

    /**
     * Get weight.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->getVariable('weight');
    }

    /**
     * Set permalink.
     *
     * @param $permalink
     *
     * @return $this
     */
    public function setPermalink($permalink)
    {
        $this->setVariable('permalink', $permalink);

        return $this;
    }

    /**
     * Get permalink.
     *
     * @return mixed|false
     */
    public function getPermalink()
    {
        if (empty($this->getVariable('permalink'))) {
            $this->setPermalink($this->getPathname());
        }

        return $this->getVariable('permalink');
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

    /**
     * Set HTML.
     *
     * @param string $html
     *
     * @return $this
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get HTML alias.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->html;
    }

    /**
     * Set layout.
     *
     * @param $layout
     *
     * @return $this
     */
    public function setLayout($layout)
    {
        $this->setVariable('layout', $layout);

        return $this;
    }

    /**
     * Get layout.
     *
     * @return mixed|false
     */
    public function getLayout()
    {
        return $this->getVariable('layout');
    }
}
