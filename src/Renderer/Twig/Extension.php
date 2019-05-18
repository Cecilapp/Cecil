<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\Twig;

use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use Leafo\ScssPhp\Compiler;
use MatthiasMullie\Minify;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Twig\Extension.
 */
class Extension extends SlugifyExtension
{
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var string
     */
    protected $outputPath;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * Constructor.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        parent::__construct(Slugify::create([
            'regexp' => Page::SLUGIFY_PATTERN,
        ]));

        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
        $this->outputPath = $this->config->getOutputPath();
        $this->fileSystem = new Filesystem();
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
            new \Twig_SimpleFilter('filterBySection', [$this, 'filterBySection']),
            new \Twig_SimpleFilter('filterBy', [$this, 'filterBy']),
            new \Twig_SimpleFilter('sortByTitle', [$this, 'sortByTitle']),
            new \Twig_SimpleFilter('sortByWeight', [$this, 'sortByWeight']),
            new \Twig_SimpleFilter('sortByDate', [$this, 'sortByDate']),
            new \Twig_SimpleFilter('urlize', [$this, 'slugifyFilter']),
            new \Twig_SimpleFilter('minifyCSS', [$this, 'minifyCss']),
            new \Twig_SimpleFilter('minifyJS', [$this, 'minifyJs']),
            new \Twig_SimpleFilter('SCSStoCSS', [$this, 'scssToCss']),
            new \Twig_SimpleFilter('excerpt', [$this, 'excerpt']),
            new \Twig_SimpleFilter('excerptHtml', [$this, 'excerptHtml']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('url', [$this, 'createUrl']),
            new \Twig_SimpleFunction('minify', [$this, 'minify']),
            new \Twig_SimpleFunction('readtime', [$this, 'readtime']),
            new \Twig_SimpleFunction('toCSS', [$this, 'toCss']),
            new \Twig_SimpleFunction('hash', [$this, 'hashFile']),
            new \Twig_SimpleFunction('getenv', [$this, 'getEnv']),
        ];
    }

    /**
     * Filter by section.
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
     * Filter by variable.
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
            // not virtual only
            if (!$page->isVirtual()) {
                $notVirtual = true;
            }
            // dedicated getter?
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
     * Sort by title.
     *
     * @param CollectionInterface|array $collection
     *
     * @return array
     */
    public function sortByTitle($collection): array
    {
        if ($collection instanceof CollectionInterface) {
            $collection = $collection->toArray();
        }
        if (is_array($collection)) {
            array_multisort(array_keys($collection), SORT_NATURAL | SORT_FLAG_CASE, $collection);
        }

        return $collection;
    }

    /**
     * Sort by weight.
     *
     * @param CollectionInterface|array $collection
     *
     * @return array
     */
    public function sortByWeight($collection): array
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

        if ($collection instanceof CollectionInterface) {
            $collection = $collection->toArray();
        }
        if (is_array($collection)) {
            usort($collection, $callback);
        }

        return $collection;
    }

    /**
     * Sort by date.
     *
     * @param CollectionInterface|array $collection
     *
     * @return mixed
     */
    public function sortByDate($collection): array
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

        if ($collection instanceof CollectionInterface) {
            $collection = $collection->toArray();
        }
        if (is_array($collection)) {
            usort($collection, $callback);
        }

        return $collection;
    }

    /**
     * Create an URL.
     *
     * $options[
     *     'canonical' => null,
     *     'addhash'   => true,
     *     'format'    => 'json',
     * ];
     *
     * @param Page|string|null $value
     * @param array|null       $options
     *
     * @return string|null
     */
    public function createUrl($value = null, $options = null): ?string
    {
        $baseurl = $this->config->get('site.baseurl');
        $hash = md5($this->config->get('site.time'));
        $base = '';
        // handle options
        $canonical = null;
        $addhash = false;
        $format = null;
        // backward compatibility
        if (is_bool($options)) {
            $oldOptions = $options;
            $options = [];
            $options['canonical'] = false;
            if ($oldOptions === true) {
                $options['canonical'] = true;
            }
        }
        extract($options ?: []);

        // set baseurl
        if ($this->config->get('site.canonicalurl') === true || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // Page item
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
        } else {
            // string
            if (preg_match('~^(?:f|ht)tps?://~i', $value)) { // external URL
                $url = $value;
            } else {
                if (false !== strpos($value, '.')) { // ressource URL (with a dot for extension)
                    $url = $value;
                    if ($addhash) {
                        $url .= '?'.$hash;
                    }
                    $url = $base.'/'.ltrim($url, '/');
                } else {
                    $url = $base.'/';
                    if (!empty($value) && $value != '/') {
                        $url = $base.'/'.$value;
                        // value == page ID?
                        $pageId = $this->slugifyFilter($value);
                        if ($this->builder->getPages()->has($pageId)) {
                            $page = $this->builder->getPages()->get($pageId);
                            $url = $this->createUrl($page, $options);
                        }
                    }
                }
            }
        }

        return $url;
    }

    /**
     * Minify a CSS or a JS file.
     *
     * @param string $path
     *
     * @throws Exception
     *
     * @return string
     */
    public function minify(string $path): string
    {
        $filePath = $this->outputPath.'/'.$path;
        if (is_file($filePath)) {
            $extension = (new \SplFileInfo($filePath))->getExtension();
            switch ($extension) {
                case 'css':
                    $minifier = new Minify\CSS($filePath);
                    break;
                case 'js':
                    $minifier = new Minify\JS($filePath);
                    break;
                default:
                    throw new Exception(sprintf("File '%s' should be a '.css' or a '.js'!", $path));
            }
            $minifier->minify($filePath);

            return $path;
        }

        throw new Exception(sprintf("File '%s' doesn't exist!", $path));
    }

    /**
     * Minify CSS.
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
     * Minify JS.
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
     * Compile style file to CSS.
     *
     * @param string $path
     *
     * @throws Exception
     *
     * @return string
     */
    public function toCss(string $path): string
    {
        $filePath = $this->outputPath.'/'.$path;
        $subPath = substr($path, 0, strrpos($path, '/'));

        if (is_file($filePath)) {
            $extension = (new \SplFileInfo($filePath))->getExtension();
            switch ($extension) {
                case 'scss':
                    $scssPhp = new Compiler();
                    $scssPhp->setImportPaths($this->outputPath.'/'.$subPath);
                    $targetPath = preg_replace('/scss/m', 'css', $path);

                    // compile if target file doesn't exists
                    if (!$this->fileSystem->exists($this->outputPath.'/'.$targetPath)) {
                        $scss = file_get_contents($filePath);
                        $css = $scssPhp->compile($scss);
                        $this->fileSystem->dumpFile($this->outputPath.'/'.$targetPath, $css);
                    }

                    return $targetPath;
                default:
                    throw new Exception(sprintf("File '%s' should be a '.scss'!", $path));
            }
        }

        throw new Exception(sprintf("File '%s' doesn't exist!", $path));
    }

    /**
     * Compile SCSS string to CSS.
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
     * Read $lenght first characters of a string and add a suffix.
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
     * Read characters before '<!-- excerpt|break -->'.
     *
     * @param string|null $string
     *
     * @return string|null
     */
    public function excerptHtml(string $string = null): ?string
    {
        // https://regex101.com/r/Xl7d5I/1/
        $pattern = '(.*)[\n\r\s]*(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)[\n\r\s]*(.*)';
        preg_match('/'.$pattern.'/i', $string, $matches);
        if (empty($matches)) {
            return $string;
        }

        return trim($matches[1]);
    }

    /**
     * Calculate estimated time to read a text.
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
     * Hash file with sha384.
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
        if (is_file($filePath = $this->outputPath.'/'.$path)) {
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
}
