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

namespace Cecil\Step\Pages;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Converter\ConverterInterface;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Psr\Log\NullLogger;

/**
 * Convert step.
 *
 * This step is responsible for converting pages from their source format
 * (i.e. Markdown) to HTML, applying front matter processing,
 * and ensuring that the pages are ready for rendering. It handles both
 * published and draft pages, depending on the build options.
 *
 * When the `pcntl` extension is available, page conversions are parallelized
 * using pcntl_fork for better performance. Each child process inherits the
 * full Builder context (pages collection, config, etc.) via copy-on-write.
 */
class Convert extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        if ($this->builder->getBuildOptions()['drafts']) {
            return 'Converting pages (drafts included)';
        }

        return 'Converting pages';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        parent::init($options);

        if (\is_null($this->builder->getPages())) {
            $this->canProcess = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        if (!is_iterable($this->builder->getPages()) || \count($this->builder->getPages()) == 0) {
            return;
        }

        // Use parallel processing if pcntl is available, otherwise fall back to sequential
        if (\function_exists('pcntl_fork')) {
            $this->builder->getLogger()->debug('Starting page conversion using parallel processing');
            $this->processParallel();

            return;
        }

        $this->builder->getLogger()->debug('Starting page conversion using sequential processing (pcntl extension not available)');
        $this->processSequential();
    }

    /**
     * Processes pages sequentially (original behavior).
     */
    protected function processSequential(): void
    {
        $total = \count($this->builder->getPages());
        $count = 0;
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $count++;

                try {
                    $convertedPage = $this->convertPage($this->builder, $page);
                    // set default language (ex: "en") if necessary
                    if ($convertedPage->getVariable('language') === null) {
                        $convertedPage->setVariable('language', $this->config->getLanguageDefault());
                    }
                } catch (RuntimeException $e) {
                    $this->builder->getLogger()->error(\sprintf('Unable to convert "%s:%s": %s', $e->getFile(), $e->getLine(), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                } catch (\Exception $e) {
                    $this->builder->getLogger()->error(\sprintf('Unable to convert "%s": %s', Util::joinPath(Util\File::getFS()->makePathRelative($page->getFilePath(), $this->config->getPagesPath())), $e->getMessage()));
                    $this->builder->getPages()->remove($page->getId());
                    continue;
                }
                $message = \sprintf('Page "%s" converted', $page->getId());
                $statusMessage = ' (not published)';
                // forces drafts convert?
                if ($this->builder->getBuildOptions()['drafts']) {
                    $page->setVariable('published', true);
                }
                // replaces page in collection
                if ($page->getVariable('published')) {
                    $this->builder->getPages()->replace($page->getId(), $convertedPage);
                    $statusMessage = '';
                }
                $this->builder->getLogger()->info($message . $statusMessage, ['progress' => [$count, $total]]);
            }
        }
    }

    /**
     * Processes pages in parallel using pcntl_fork.
     *
     * Each child process inherits the parent's memory (copy-on-write), so the full Builder context (pages collection, config, converters, etc.) is available.
     * Only the conversion results (variables array + HTML string) are serialized back to the parent via temporary files.
     */
    protected function processParallel(): void
    {
        // Collect non-virtual pages
        $pages = [];
        /** @var Page $page */
        foreach ($this->builder->getPages() as $page) {
            if (!$page->isVirtual()) {
                $pages[] = $page;
            }
        }

        $total = \count($pages);
        if ($total === 0) {
            return;
        }

        $concurrency = $this->getConcurrency($total);
        $this->builder->getLogger()->debug(\sprintf('Using concurrency level: %d (total pages: %d)', $concurrency, $total));
        $chunks = \array_chunk($pages, max(1, (int) ceil($total / $concurrency)));
        $format = (string) $this->builder->getConfig()->get('pages.frontmatter');
        $drafts = (bool) $this->options['drafts'];

        $children = [];

        foreach ($chunks as $chunk) {
            $tmpFile = \tempnam(\sys_get_temp_dir(), 'cecil_convert_');
            if ($tmpFile === false) {
                throw new RuntimeException('Unable to create temporary file for pages conversion.');
            }

            $pid = \pcntl_fork();

            if ($pid === -1) {
                // Fork failed: convert this chunk sequentially in the parent
                $this->convertChunk($chunk, $format, $drafts, $tmpFile);
                $children[] = ['pid' => null, 'tmpFile' => $tmpFile, 'pages' => $chunk];
                continue;
            }

            if ($pid === 0) {
                // Child process
                // Silence logger to avoid output conflicts with parent
                $this->builder->setLogger(new NullLogger());

                $this->convertChunk($chunk, $format, $drafts, $tmpFile);

                // Exit child without running parent's shutdown handlers
                exit(0);
            }

            // Parent process
            $children[] = ['pid' => $pid, 'tmpFile' => $tmpFile, 'pages' => $chunk];
        }

        // Wait for all child processes and apply results
        $count = 0;
        foreach ($children as $child) {
            if ($child['pid'] !== null) {
                \pcntl_waitpid($child['pid'], $status);
            }
            $count = $this->applyChunkResults($child['pages'], $child['tmpFile'], $total, $count);
        }
    }

    /**
     * Converts a chunk of pages using the existing Builder and writes
     * results to a temporary file.
     *
     * Each page's conversion output (front matter variables + HTML body) is
     * stored as serializable primitives (arrays, strings).
     *
     * @param Page[] $pages
     */
    protected function convertChunk(array $pages, string $format, bool $drafts, string $tmpFile): void
    {
        $converter = new Converter($this->builder);
        $results = [];

        foreach ($pages as $page) {
            $pageId = $page->getId();

            try {
                $variables = null;
                $html = null;

                // Convert front matter
                if ($page->getFrontmatter()) {
                    $variables = $converter->convertFrontmatter($page->getFrontmatter(), $format);
                }

                // Determine effective published status by applying variables to a cloned page
                // This ensures side effects (e.g., schedule logic) are applied, matching convertPage() behavior
                $published = (bool) $page->getVariable('published');
                if ($variables !== null) {
                    $tempPage = clone $page;
                    $tempPage->setVariables($variables);
                    $published = (bool) $tempPage->getVariable('published');
                }

                // Convert body (only if page is published or drafts option is enabled)
                if ($published || $drafts) {
                    $html = $converter->convertBody($page->getBody() ?? '');
                }

                $results[$pageId] = [
                    'success' => true,
                    'variables' => $variables,
                    'html' => $html,
                ];
            } catch (\Throwable $e) {
                $results[$pageId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        \file_put_contents($tmpFile, \serialize($results));
    }

    /**
     * Reads conversion results from a temporary file and applies them
     * to the original Page objects in the parent process.
     *
     * @param Page[] $pages
     *
     * @return int Updated progress count
     */
    protected function applyChunkResults(array $pages, string $tmpFile, int $total, int $count): int
    {
        $results = [];

        if (\file_exists($tmpFile)) {
            $data = \file_get_contents($tmpFile);
            if ($data !== false && $data !== '') {
                $unserialized = @\unserialize($data, ['allowed_classes' => [\DateTimeImmutable::class]]);
                if (\is_array($unserialized)) {
                    $results = $unserialized;
                } else {
                    $this->builder->getLogger()->warning(\sprintf('Invalid conversion results in temporary file "%s". Ignoring.', $tmpFile));
                }
            }
            @\unlink($tmpFile);
        }

        foreach ($pages as $page) {
            $pageId = $page->getId();
            $count++;

            // If results are missing or conversion failed, remove the page
            if (!isset($results[$pageId]) || !$results[$pageId]['success']) {
                $error = $results[$pageId]['error'] ?? 'Unknown error';
                $this->builder->getLogger()->error(\sprintf('Unable to convert "%s": %s', $pageId, $error));
                $this->builder->getPages()->remove($pageId);
                continue;
            }

            $result = $results[$pageId];

            // Apply front matter variables
            if ($result['variables'] !== null) {
                $page->setFmVariables($result['variables']);
                $page->setVariables($result['variables']);
            }

            // Apply converted HTML body
            if ($result['html'] !== null) {
                $page->setBodyHtml($result['html']);
            }

            // Set default language if necessary
            if ($page->getVariable('language') === null) {
                $page->setVariable('language', $this->config->getLanguageDefault());
            }

            $message = \sprintf('Page "%s" converted (%s)', $pageId, $tmpFile);
            $statusMessage = ' (not published)';

            // Forces drafts convert?
            if ($this->builder->getBuildOptions()['drafts']) {
                $page->setVariable('published', true);
            }

            // Replaces page in collection
            if ($page->getVariable('published')) {
                $this->builder->getPages()->replace($pageId, $page);
                $statusMessage = '';
            }

            $this->builder->getLogger()->info($message . $statusMessage, ['progress' => [$count, $total]]);
        }

        return $count;
    }

    /**
     * Determines the optimal concurrency level based on CPU count.
     */
    protected function getConcurrency(int $totalPages): int
    {
        $cpuCount = 4; // sensible default

        if (PHP_OS_FAMILY === 'Darwin') {
            $result = @\shell_exec('sysctl -n hw.ncpu 2>/dev/null');
            if ($result !== null) {
                $cpuCount = max(1, (int) trim($result));
            }
        } elseif (\is_file('/proc/cpuinfo')) {
            $content = @\file_get_contents('/proc/cpuinfo');
            if ($content !== false) {
                $cpuCount = max(1, \substr_count($content, 'processor'));
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $result = @\shell_exec('echo %NUMBER_OF_PROCESSORS% 2>NUL');
            if ($result !== null) {
                $cpuCount = max(1, (int) trim($result));
            }
        }

        return min($cpuCount, $totalPages);
    }

    /**
     * Converts page content:
     *  - front matter to PHP array
     *  - body to HTML.
     *
     * @throws RuntimeException
     */
    public function convertPage(Builder $builder, Page $page, ?string $format = null, ?ConverterInterface $converter = null): Page
    {
        $format = $format ?? (string) $builder->getConfig()->get('pages.frontmatter');
        $converter = $converter ?? new Converter($builder);

        // converts front matter
        if ($page->getFrontmatter()) {
            try {
                $variables = $converter->convertFrontmatter($page->getFrontmatter(), $format);
            } catch (RuntimeException $e) {
                throw new RuntimeException($e->getMessage(), file: $page->getFilePath(), line: $e->getLine());
            }
            $page->setFmVariables($variables);
            $page->setVariables($variables);
        }

        // converts body (only if page is published or drafts option is enabled)
        if ($page->getVariable('published') || $this->options['drafts']) {
            try {
                $html = $converter->convertBody($page->getBody());
            } catch (RuntimeException $e) {
                throw new \Exception($e->getMessage());
            }
            $page->setBodyHtml($html);
        }

        return $page;
    }
}
