<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Exception\RuntimeException;

/**
 * Class Generator\VirtualPages.
 */
class VirtualPages extends AbstractGenerator implements GeneratorInterface
{
    protected $configKey = 'virtualpages';

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
                $id = !empty($path) ? $path : 'index';
                if ($language['code'] !== $this->config->getLanguageDefault()) {
                    $id .= '.'.$language['code'];
                    if (isset($frontmatter['multilingual']) && $frontmatter['multilingual'] === false) {
                        continue;
                    }
                }
                if ($this->builder->getPages()->has($id)) {
                    continue;
                }
                $page = (new Page($id))
                    ->setPath($path)
                    ->setType(Type::PAGE)
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
