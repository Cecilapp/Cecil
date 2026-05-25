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

namespace Cecil\Renderer\Extension;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Converter\Parsedown;
use Cecil\Exception\RuntimeException;
use Highlight\Highlighter;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Twig\Extension\AbstractExtension;

/**
 * Content Twig extension.
 *
 * Provides filters and functions for content processing in Twig templates,
 * including text manipulation, Markdown rendering, and data parsing.
 */
class Content extends AbstractExtension
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('readtime', [$this, 'readtime']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('slugify', [$this, 'slugifyFilter']),
            new \Twig\TwigFilter('excerpt', [$this, 'excerpt']),
            new \Twig\TwigFilter('excerpt_html', [$this, 'excerptHtml']),
            new \Twig\TwigFilter('markdown_to_html', [$this, 'markdownToHtml']),
            new \Twig\TwigFilter('toc', [$this, 'markdownToToc']),
            new \Twig\TwigFilter('json_decode', [$this, 'jsonDecode']),
            new \Twig\TwigFilter('yaml_parse', [$this, 'yamlParse']),
            new \Twig\TwigFilter('preg_split', [$this, 'pregSplit']),
            new \Twig\TwigFilter('preg_match_all', [$this, 'pregMatchAll']),
            new \Twig\TwigFilter('hex_to_rgb', [$this, 'hexToRgb']),
            new \Twig\TwigFilter('splitline', [$this, 'splitLine']),
            new \Twig\TwigFilter('iterable', [$this, 'iterable']),
            new \Twig\TwigFilter('highlight', [$this, 'highlight']),
            new \Twig\TwigFilter('unique', [$this, 'unique']),
            // date
            new \Twig\TwigFilter('duration_to_iso8601', ['\Cecil\Util\Date', 'durationToIso8601']),
        ];
    }

    /**
     * Slugifies a string.
     */
    public function slugifyFilter(string $string): string
    {
        return Page::slugify($string);
    }

    /**
     * Reads $length first characters of a string and adds a suffix.
     */
    public function excerpt(?string $string, int $length = 450, string $suffix = ' …'): string
    {
        $string = $string ?? '';

        $string = str_replace('</p>', '<br><br>', $string);
        $string = trim(strip_tags($string, '<br>'));
        if (mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length);
            $string .= $suffix;
        }

        return $string;
    }

    /**
     * Reads characters before or after '<!-- separator -->'.
     * Options:
     *  - separator: string to use as separator (`excerpt|break` by default)
     *  - capture: part to capture, `before` or `after` the separator (`before` by default).
     */
    public function excerptHtml(?string $string, array $options = []): string
    {
        $string = $string ?? '';

        $separator = (string) $this->config->get('pages.body.excerpt.separator');
        $capture = (string) $this->config->get('pages.body.excerpt.capture');
        extract($options, EXTR_IF_EXISTS);

        // https://regex101.com/r/n9TWHF/1
        $pattern = '(.*)<!--[[:blank:]]?(' . $separator . ')[[:blank:]]?-->(.*)';
        preg_match('/' . $pattern . '/is', $string, $matches);

        if (empty($matches)) {
            return $string;
        }
        $result = trim($matches[1]);
        if ($capture == 'after') {
            $result = trim($matches[3]);
        }
        // removes footnotes and returns result
        return preg_replace('/<sup[^>]*>[^u]*<\/sup>/', '', $result);
    }

    /**
     * Converts a Markdown string to HTML.
     *
     * @throws RuntimeException
     */
    public function markdownToHtml(?string $markdown): ?string
    {
        $markdown = $markdown ?? '';

        try {
            $parsedown = new Parsedown($this->builder);
            $html = $parsedown->text($markdown);
        } catch (\Exception $e) {
            throw new RuntimeException(
                '"markdown_to_html" filter can not convert supplied Markdown.',
                previous: $e
            );
        }

        return $html;
    }

    /**
     * Extracts only headings matching the given `selectors` (h2, h3, etc.),
     * or those defined in config `pages.body.toc` if not specified.
     * The `format` parameter defines the output format: `html` or `json`.
     * The `url` parameter is used to build links to headings.
     *
     * @throws RuntimeException
     */
    public function markdownToToc(?string $markdown, $format = 'html', ?array $selectors = null, string $url = ''): ?string
    {
        $markdown = $markdown ?? '';
        $selectors = $selectors ?? (array) $this->config->get('pages.body.toc');

        try {
            $parsedown = new Parsedown($this->builder, ['selectors' => $selectors, 'base_url' => $url]);
            $parsedown->body($markdown);
            $return = $parsedown->contentsList($format);
        } catch (\Exception) {
            throw new RuntimeException('"toc" filter can not convert supplied Markdown.');
        }

        return $return;
    }

    /**
     * Converts a JSON string to an array.
     *
     * @throws RuntimeException
     */
    public function jsonDecode(?string $json): ?array
    {
        $json = $json ?? '';

        try {
            $array = json_decode($json, true);
            if ($array === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON error.');
            }
        } catch (\Exception) {
            throw new RuntimeException('"json_decode" filter can not parse supplied JSON.');
        }

        return $array;
    }

    /**
     * Converts a YAML string to an array.
     *
     * @throws RuntimeException
     */
    public function yamlParse(?string $yaml): ?array
    {
        $yaml = $yaml ?? '';

        try {
            $array = Yaml::parse($yaml, Yaml::PARSE_DATETIME);
            if (!\is_array($array)) {
                throw new ParseException('YAML error.');
            }
        } catch (ParseException $e) {
            throw new RuntimeException(\sprintf('"yaml_parse" filter can not parse supplied YAML: %s', $e->getMessage()));
        }

        return $array;
    }

    /**
     * Split a string into an array using a regular expression.
     *
     * @throws RuntimeException
     */
    public function pregSplit(?string $value, string $pattern, int $limit = 0): ?array
    {
        $value = $value ?? '';

        try {
            $array = preg_split($pattern, $value, $limit);
            if ($array === false) {
                throw new RuntimeException('PREG split error.');
            }
        } catch (\Exception) {
            throw new RuntimeException('"preg_split" filter can not split supplied string.');
        }

        return $array;
    }

    /**
     * Perform a regular expression match and return the group for all matches.
     *
     * @throws RuntimeException
     */
    public function pregMatchAll(?string $value, string $pattern, int $group = 0): ?array
    {
        $value = $value ?? '';

        try {
            $array = preg_match_all($pattern, $value, $matches, PREG_PATTERN_ORDER);
            if ($array === false) {
                throw new RuntimeException('PREG match all error.');
            }
        } catch (\Exception) {
            throw new RuntimeException('"preg_match_all" filter can not match in supplied string.');
        }

        return $matches[$group];
    }

    /**
     * Calculates estimated time to read a text.
     */
    public function readtime(?string $text): string
    {
        $text = $text ?? '';

        $words = str_word_count(strip_tags($text));
        $min = floor($words / 200);
        if ($min === 0) {
            return '1';
        }

        return (string) $min;
    }

    /**
     * Converts an hexadecimal color to RGB.
     *
     * @throws RuntimeException
     */
    public function hexToRgb(?string $variable): array
    {
        $variable = $variable ?? '';

        if (!self::isHex($variable)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid hexadecimal value.', $variable));
        }
        $hex = ltrim($variable, '#');
        if (\strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $c = hexdec($hex);

        return [
            'red'   => $c >> 16 & 0xFF,
            'green' => $c >> 8 & 0xFF,
            'blue'  => $c & 0xFF,
        ];
    }

    /**
     * Split a string in multiple lines.
     */
    public function splitLine(?string $variable, int $max = 18): array
    {
        $variable = $variable ?? '';

        return preg_split("/.{0,{$max}}\K(\s+|$)/", $variable, 0, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Converts a variable to an iterable (array).
     */
    public function iterable($value): array
    {
        if (\is_array($value)) {
            return $value;
        }
        if (\is_string($value)) {
            return [$value];
        }
        if ($value instanceof \Traversable) {
            return iterator_to_array($value);
        }
        if ($value instanceof \stdClass) {
            return (array) $value;
        }
        if (\is_object($value)) {
            return [$value];
        }
        if (\is_int($value) || \is_float($value)) {
            return [$value];
        }
        return [$value];
    }

    /**
     * Highlights a code snippet.
     */
    public function highlight(string $code, string $language): string
    {
        return (new Highlighter())->highlight($language, $code)->value;
    }

    /**
     * Returns an array with unique values.
     */
    public function unique(array $array): array
    {
        return array_intersect_key($array, array_unique(array_map('strtolower', $array), SORT_STRING));
    }

    /**
     * Is a hexadecimal color is valid?
     */
    private static function isHex(string $hex): bool
    {
        $valid = \is_string($hex);
        $hex = ltrim($hex, '#');
        $length = \strlen($hex);
        $valid = $valid && ($length === 3 || $length === 6);
        $valid = $valid && ctype_xdigit($hex);

        return $valid;
    }
}
