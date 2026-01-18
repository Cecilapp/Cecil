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

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Exception\RuntimeException;

/**
 * VirtualPages class.
 *
 * This class is responsible for generating virtual pages based on the configuration provided.
 * It extends the AbstractGenerator and implements the GeneratorInterface.
 * It collects pages from the configuration under the 'pages.virtual' key and creates Page objects
 * for each virtual page defined in the configuration.
 * Each page can have its own frontmatter, and the generator ensures that the pages are created
 * with the correct path and language settings.
 * If a page is marked as unpublished or does not have a path defined, it will be skipped.
 * If a page already exists with the same ID, it will also be skipped.
 * The generated pages are added to the collection of generated pages for further processing.
 */
class VirtualPages extends AbstractGenerator implements GeneratorInterface
{
    protected $configKey = 'pages.virtual';

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $pagesConfig = $this->collectPagesFromConfig($this->configKey);

        if (!$pagesConfig) {
            return;
        }

        foreach ($pagesConfig as $frontmatter) {
            if (isset($frontmatter['published']) && $frontmatter['published'] === false) {
                continue;
            }
            if (!isset($frontmatter['path'])) {
                throw new RuntimeException(\sprintf('Each pages in "%s" config\'s section must have a "path".', $this->configKey));
            }
            $path = Page::slugify($frontmatter['path']);
            foreach ($this->config->getLanguages() as $language) {
                $pageId = !empty($path) ? $path : 'index';
                if ($language['code'] !== $this->config->getLanguageDefault()) {
                    $pageId = \sprintf('%s/%s', $language['code'], $pageId);
                    // disable multilingual support
                    if (isset($frontmatter['multilingual']) && $frontmatter['multilingual'] === false) {
                        continue;
                    }
                }
                // abord if the page id already exists
                if ($this->builder->getPages() && $this->builder->getPages()->has($pageId)) {
                    continue;
                }
                $page = (new Page($pageId))
                    ->setPath($path)
                    ->setType(Type::PAGE->value)
                    ->setVariable('language', $language['code'])
                    ->setVariable('langref', $path);
                $page->setVariables($frontmatter);
                $this->generatedPages->add($page);
            }
        }
    }

    /**
     * Collects virtual pages configuration.
     */
    private function collectPagesFromConfig(string $configKey): ?array
    {
        return $this->config->get($configKey);
    }
}
