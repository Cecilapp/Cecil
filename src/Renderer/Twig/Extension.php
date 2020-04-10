<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\Twig;

use Cecil\Assets\Asset;
use Cecil\Assets\Image;
use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Compiler;

/**
 * Class Twig\Extension.
 */
class Extension extends SlugifyExtension
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var Slugify */
    private static $slugifier;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => Page::SLUGIFY_PATTERN]);
        }

        parent::__construct(self::$slugifier);

        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cecil';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('filterBySection', [$this, 'filterBySection']),
            new \Twig\TwigFilter('filterBy', [$this, 'filterBy']),
            new \Twig\TwigFilter('sortByTitle', [$this, 'sortByTitle']),
            new \Twig\TwigFilter('sortByWeight', [$this, 'sortByWeight']),
            new \Twig\TwigFilter('sortByDate', [$this, 'sortByDate']),
            new \Twig\TwigFilter('urlize', [$this, 'slugifyFilter']),
            new \Twig\TwigFilter('minifyCSS', [$this, 'minifyCss']),
            new \Twig\TwigFilter('minifyJS', [$this, 'minifyJs']),
            new \Twig\TwigFilter('SCSStoCSS', [$this, 'scssToCss']),
            new \Twig\TwigFilter('excerpt', [$this, 'excerpt']),
            new \Twig\TwigFilter('excerptHtml', [$this, 'excerptHtml']),
            new \Twig\TwigFilter('resize', [$this, 'resize']),
            new \Twig\TwigFilter('url', [$this, 'createUrl']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('url', [$this, 'createUrl']),
            new \Twig\TwigFunction('minify', [$this, 'minify']),
            new \Twig\TwigFunction('readtime', [$this, 'readtime']),
            new \Twig\TwigFunction('toCSS', [$this, 'toCss']),
            new \Twig\TwigFunction('hash', [$this, 'hashFile']),
            new \Twig\TwigFunction('getenv', [$this, 'getEnv']),
            new \Twig\TwigFunction('asset', [$this, 'asset']),
        ];
    }

    /**
     * Filters by Section.
     *
     * Alias of `filterBy('section', $value)`.
     *
     * @param PagesCollection $pages
     * @param string          $section
     *
     * @return CollectionInterface
     */
    public function filterBySection(PagesCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filters by variable's name/value.
     *
     * @param PagesCollection $pages
     * @param string          $variable
     * @param string          $value
     *
     * @return CollectionInterface
     */
    public function filterBy(PagesCollection $pages, string $variable, string $value): CollectionInterface
    {
        $filteredPages = $pages->filter(function (Page $page) use ($variable, $value) {
            $notVirtual = false;
            if (!$page->isVirtual()) {
                $notVirtual = true;
            }
            // is a dedicated getter exists?
            $method = 'get'.ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return $notVirtual && true;
            }
            if ($page->getVariable($variable) == $value) {
                return $notVirtual && true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sorts by title.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByTitle(\Traversable $collection): array
    {
        $collection = iterator_to_array($collection);
        array_multisort(array_keys($collection), SORT_NATURAL | SORT_FLAG_CASE, $collection);

        return $collection;
    }

    /**
     * Sorts by weight.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByWeight(\Traversable $collection): array
    {
        $callback = function ($a, $b) {
            if (!isset($a['weight'])) {
                return 1;
            }
            if (!isset($b['weight'])) {
                return -1;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort($collection, $callback);

        return $collection;
    }

    /**
     * Sorts by date.
     *
     * @param \Traversable $collection
     *
     * @return array
     */
    public function sortByDate(\Traversable $collection): array
    {
        $callback = function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort($collection, $callback);

        return $collection;
    }

    /**
     * Creates an URL.
     *
     * $options[
     *     'canonical' => null,
     *     'addhash'   => true,
     *     'format'    => 'json',
     * ];
     *
     * @param Page|string|null $value
     * @param array|bool|null  $options
     *
     * @return string|null
     */
    public function createUrl($value = null, $options = null): ?string
    {
        $baseurl = (string) $this->config->get('baseurl');
        $hash = md5((string) $this->config->get('time'));
        $base = '';
        // handles options
        $canonical = null;
        $addhash = false;
        $format = null;
        extract(is_array($options) ? $options : []);

        // set baseurl
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // value is a Page item
        if ($value instanceof Page) {
            if (!$format) {
                $format = $value->getVariable('output');
                if (is_array($value->getVariable('output'))) {
                    $format = $value->getVariable('output')[0];
                }
                if (!$format) {
                    $format = 'html';
                }
            }
            $url = $value->getUrl($format, $this->config);
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // value is an external URL
        if ($value !== null) {
            if (Util::isExternalUrl($value)) {
                $url = $value;

                return $url;
            }
        }

        // value is a string
        if (!is_null($value)) {
            // value is an external URL
            if (Util::isExternalUrl($value)) {
                $url = $value;

                return $url;
            }
            $value = Util::joinPath($value);
        }

        // value is a ressource URL (ie: 'path/style.css')
        if (false !== strpos($value, '.')) {
            $url = $value;
            if ($addhash) {
                $url .= '?'.$hash;
            }
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // others cases
        $url = $base.'/';
        if (!empty($value) && $value != '/') {
            $url = $base.'/'.$value;

            // value is a page ID (ie: 'path/my-page')
            try {
                $pageId = $this->slugifyFilter($value);
                $page = $this->builder->getPages()->get($pageId);
                $url = $this->createUrl($page, $options);
            } catch (\DomainException $e) {
                // nothing to do
            }
        }

        return $url;
    }

    /**
     * Minifying a CSS or a JS file.
     *
     * ie: minify('css/style.css')
     *
     * @param string $path
     *
     * @throws Exception
     *
     * @return string
     */
    public function minify(string $path): string
    {
        $filePath = Util::joinFile($this->config->getOutputPath(), $path);
        $fileInfo = new \SplFileInfo($filePath);
        $fileExtension = $fileInfo->getExtension();
        // ie: minify('css/style.min.css')
        $pathMinified = \sprintf('%s.min.%s', substr($path, 0, -strlen(".$fileExtension")), $fileExtension);
        $filePathMinified = Util::joinFile($this->config->getOutputPath(), $pathMinified);
        if (is_file($filePathMinified)) {
            return $pathMinified;
        }
        if (is_file($filePath)) {
            switch ($fileExtension) {
                case 'css':
                    $minifier = new Minify\CSS($filePath);
                    break;
                case 'js':
                    $minifier = new Minify\JS($filePath);
                    break;
                default:
                    throw new Exception(sprintf('%s() error: not able to process "%s"', __FUNCTION__, $path));
            }
            Util::getFS()->mkdir(dirname($filePathMinified));
            $minifier->minify($filePathMinified);

            return $pathMinified;
        }

        throw new Exception(sprintf('%s() error: "%s" doesn\'t exist', __FUNCTION__, $path));
    }

    /**
     * Minifying CSS.
     *
     * @param string $value
     *
     * @return string
     */
    public function minifyCss(string $value): string
    {
        $minifier = new Minify\CSS($value);

        return $minifier->minify();
    }

    /**
     * Minifying JS.
     *
     * @param string $value
     *
     * @return string
     */
    public function minifyJs(string $value): string
    {
        $minifier = new Minify\JS($value);

        return $minifier->minify();
    }

    /**
     * Compiles a SCSS file to CSS.
     *
     * @param string $path
     *
     * @throws Exception
     *
     * @return string
     */
    public function toCss(string $path): string
    {
        $filePath = Util::joinFile($this->config->getOutputPath(), $path);
        $subPath = substr($path, 0, strrpos($path, '/'));

        if (is_file($filePath)) {
            $fileExtension = (new \SplFileInfo($filePath))->getExtension();
            switch ($fileExtension) {
                case 'scss':
                    $scssPhp = new Compiler();
                    $scssPhp->setImportPaths(Util::joinFile($this->config->getOutputPath(), $subPath));
                    $targetPath = preg_replace('/scss/m', 'css', $path);

                    // compiles if target file doesn't exists
                    if (!Util::getFS()->exists(Util::joinFile($this->config->getOutputPath(), $targetPath))) {
                        $scss = file_get_contents($filePath);
                        $css = $scssPhp->compile($scss);
                        Util::getFS()->dumpFile(Util::joinFile($this->config->getOutputPath(), $targetPath), $css);
                    }

                    return $targetPath;
                default:
                    throw new Exception(sprintf('%s() error: not able to process "%s"', __FUNCTION__, $path));
            }
        }

        throw new Exception(sprintf('%s() error: "%s" doesn\'t exist', __FUNCTION__, $path));
    }

    /**
     * Compiles SCSS string to CSS.
     *
     * @param string $value
     *
     * @return string
     */
    public function scssToCss(string $value): string
    {
        $scss = new Compiler();

        return $scss->compile($value);
    }

    /**
     * Reads $length first characters of a string and adds a suffix.
     *
     * @param string|null $string
     * @param int         $length
     * @param string      $suffix
     *
     * @return string|null
     */
    public function excerpt(string $string = null, int $length = 450, string $suffix = ' …'): ?string
    {
        $string = str_replace('</p>', '<br /><br />', $string);
        $string = trim(strip_tags($string, '<br>'), '<br />');
        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Reads characters before '<!-- excerpt|break -->'.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public function excerptHtml(string $string = null): ?string
    {
        // https://regex101.com/r/Xl7d5I/3
        $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
        preg_match('/'.$pattern.'/is', $string, $matches);
        if (empty($matches)) {
            return $string;
        }

        return trim($matches[1]);
    }

    /**
     * Calculates estimated time to read a text.
     *
     * @param string|null $text
     *
     * @return string
     */
    public function readtime(string $text = null): string
    {
        $words = str_word_count(strip_tags($text));
        $min = floor($words / 200);
        if ($min === 0) {
            return '1';
        }

        return (string) $min;
    }

    /**
     * Hashing a file with sha384.
     *
     * Useful for SRI (Subresource Integrity).
     *
     * @see https://developer.mozilla.org/fr/docs/Web/Security/Subresource_Integrity
     *
     * @param string $path
     *
     * @return string|null
     */
    public function hashFile(string $path): ?string
    {
        if (is_file($filePath = Util::joinFile($this->config->getOutputPath(), $path))) {
            $path = $filePath;
        }

        return sprintf('sha384-%s', base64_encode(hash_file('sha384', $path, true)));
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $var
     *
     * @return string|null
     */
    public function getEnv(string $var): ?string
    {
        return getenv($var) ?: null;
    }

    /**
     * Resizes an image.
     *
     * @param string $path Image path (relative from static/ dir or external).
     * @param int    $size Image new size (width).
     *
     * @return string
     */
    public function resize(string $path, int $size): string
    {
        return (new Image($this->builder))->resize($path, $size);
    }

    /**
     * Manages assets (css, js, images, etc.).
     *
     * @param string $path File path (relative from static/ dir).
     * @param array|null $options
     *
     * @return string
     */
    public function asset(string $path, array $options = null): Asset
    {
        return (new Asset($this->builder))->getFile($path, $options);
    }
}
