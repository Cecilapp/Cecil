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
use Cecil\Util;

/**
 * DefaultPages generator class.
 *
 * This class is responsible for generating pages without content file (front matter only),
 * declared in the configuration under the 'pages.default' key, indexed by ID.
 * It includes built-in pages (i.e.: 'index', '404', 'robots', 'sitemap') and pages added by themes or by the site.
 * Each page can have its own front matter, and the generator ensures that the pages are created
 * with the correct path and language settings.
 * If a page is marked as unpublished, it will be skipped.
 * If a page already exists with the same ID (i.e.: created from a content file), it will also be skipped.
 */
class DefaultPages extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function generate(): void
    {
        /** @var array<int|string, array<string, mixed>>|null $pagesConfig */
        $pagesConfig = $this->config->get('pages.default');

        if (!$pagesConfig) {
            return;
        }

        foreach ($pagesConfig as $frontmatter) {
            // the structure of each page is validated by the configuration schema
            if (isset($frontmatter['published']) && $frontmatter['published'] === false) {
                continue;
            }
            $path = Util\Slugifier::slugify((string) $frontmatter['path']);
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
                if ($this->builder->getPages()->has($pageId)) {
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
}
