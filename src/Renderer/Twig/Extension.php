<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\Twig;

use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PageCollection;
use Cecil\Collection\Page\Page;
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
     * @var string
     */
    protected $destPath;
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * Constructor.
     *
     * @param string $destPath
     */
    public function __construct($destPath)
    {
        $this->destPath = $destPath;
        parent::__construct(Slugify::create([
            'regexp' => Page::SLUGIFY_PATTERN,
        ]));

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
            new \Twig_SimpleFunction('url', [$this, 'createUrl'], ['needs_environment' => true]),
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
     * @param PageCollection $pages
     * @param string         $section
     *
     * @return CollectionInterface
     */
    public function filterBySection(PageCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filter by variable.
     *
     * @param PageCollection $pages
     * @param string         $variable
     * @param string         $value
     *
     * @throws Exception
     *
     * @return CollectionInterface
     */
    public function filterBy(PageCollection $pages, string $variable, string $value): CollectionInterface
    {
        $filteredPages = $pages->filter(function (Page $page) use ($variable, $value) {
            // dedicated getter?
            $method = 'get'.ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return true;
            }
            if ($page->getVariable($variable) == $value) {
                return true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sort by title.
     *
     * @param CollectionInterface|array $array
     *
     * @return mixed
     */
    public function sortByTitle($array)
    {
        if ($array instanceof CollectionInterface) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            array_multisort(array_keys($array), SORT_NATURAL | SORT_FLAG_CASE, $array);
        }

        return $array;
    }

    /**
     * Sort by weight.
     *
     * @param CollectionInterface|array $array
     *
     * @return mixed
     */
    public function sortByWeight($array)
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

        if ($array instanceof CollectionInterface) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            usort($array, $callback);
        }

        return $array;
    }

    /**
     * Sort by date.
     *
     * @param CollectionInterface|array $array
     *
     * @return mixed
     */
    public function sortByDate($array)
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

        if ($array instanceof CollectionInterface) {
            $array = $array->toArray();
        }
        if (is_array($array)) {
            usort($array, $callback);
        }

        return $array;
    }

    /**
     * Create an URL.
     *
     * $options[
     *     'canonical' => null,
     *     'addhash'   => true,
     * ];
     *
     * @param \Twig_Environment $env
     * @param string|Page|null  $value
     * @param array|null        $options
     *
     * @return string|null
     */
    public function createUrl(\Twig_Environment $env, $value = null, $options = null)
    {
        $base = '';
        $baseurl = $env->getGlobals()['site']['baseurl'];
        $hash = md5($env->getGlobals()['site']['time']);
        $canonical = null;
        $addhash = true;

        if (isset($options['canonical'])) {
            $canonical = $options['canonical'];
        }
        if (is_bool($options)) { // backward compatibility
            $canonical = $options;
        }
        if (isset($options['addhash'])) {
            $addhash = $options['addhash'];
        }

        if ($env->getGlobals()['site']['canonicalurl'] === true || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        if ($value instanceof Page) {
            $value = $value->getPermalink();
            if (false !== strpos($value, '.')) { // file URL (with a dot for extension)
                $url = $base.'/'.ltrim($value, '/');
            } else {
                $url = $base.'/'.ltrim(rtrim($value, '/').'/', '/');
            }
        } else {
            if (preg_match('~^(?:f|ht)tps?://~i', $value)) { // external URL
                $url = $value;
            } elseif (false !== strpos($value, '.')) { // file URL (with a dot for extension)
                $url = $base.'/'.ltrim($value, '/');
                if ($addhash) {
                    $url .= '?'.$hash;
                }
            } else {
                $url = $base.'/';
                if (!empty($value)) {
                    $value = $this->slugifyFilter($value);
                    $url .= ltrim(rtrim($value, '/').'/', '/');
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
        $filePath = $this->destPath.'/'.$path;
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
        $filePath = $this->destPath.'/'.$path;
        $subPath = substr($path, 0, strrpos($path, '/'));

        if (is_file($filePath)) {
            $extension = (new \SplFileInfo($filePath))->getExtension();
            switch ($extension) {
                case 'scss':
                    $scssPhp = new Compiler();
                    $scssPhp->setImportPaths($this->destPath.'/'.$subPath);
                    $targetPath = preg_replace('/scss/m', 'css', $path);

                    // compile if target file doesn't exists
                    if (!$this->fileSystem->exists($this->destPath.'/'.$targetPath)) {
                        $scss = file_get_contents($filePath);
                        $css = $scssPhp->compile($scss);
                        $this->fileSystem->dumpFile($this->destPath.'/'.$targetPath, $css);
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
     * @param string $string
     * @param int    $length
     * @param string $suffix
     *
     * @return string
     */
    public function excerpt(string $string, int $length = 450, string $suffix = ' …'): string
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
     * Read characters before '<!-- excerpt -->'.
     *
     * @param string $string
     *
     * @return string
     */
    public function excerptHtml(string $string): string
    {
        // https://regex101.com/r/mA2mG0/3
        $pattern = '^(.*)[\n\r\s]*<!-- excerpt -->[\n\r\s]*(.*)$';
        preg_match(
            '/'.$pattern.'/s',
            $string,
            $matches
        );
        if (empty($matches)) {
            return $string;
        }

        return trim($matches[1]);
    }

    /**
     * Calculate estimated time to read a text.
     *
     * @param string $text
     *
     * @return string
     */
    public function readtime(string $text): string
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
     *
     * @param string $path
     *
     * @return string|null
     */
    public function hashFile(string $path): ?string
    {
        if (is_file($filePath = $this->destPath.'/'.$path)) {
            return sprintf('sha384-%s', base64_encode(hash_file('sha384', $filePath, true)));
        }
    }

    /**
     * Gets the value of an environment variable.
     *
     * @param string $var
     *
     * @return string|false
     */
    public function getEnv($var): ?string
    {
        return getenv($var);
    }
}
