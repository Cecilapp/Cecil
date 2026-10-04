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

use Cecil\BuildContextInterface;
use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Exception\RuntimeException;
use Cecil\Util;

/**
 * Generator abstract class.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /** @var BuildContextInterface */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /** @var PagesCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function __construct(BuildContextInterface $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        // Creates a new empty collection
        $this->generatedPages = new PagesCollection('generator-' . Util::formatClassName($this, ['lowercase' => true]));
    }

    /**
     * Returns the concrete Builder, required by components that depend on more than BuildContextInterface.
     *
     * @throws RuntimeException
     */
    protected function getBuilder(): Builder
    {
        if (!$this->builder instanceof Builder) {
            throw new RuntimeException(\sprintf('"%s" requires an instance of "%s".', static::class, Builder::class));
        }

        return $this->builder;
    }

    /**
     * Run the `generate` method of the generator and returns pages.
     */
    public function runGenerate(): PagesCollection
    {
        $this->generate();

        // set default language (e.g.: "en") if necessary
        $this->generatedPages->map(function (\Cecil\Collection\Page\Page $page) {
            if ($page->getVariable('language') === null) {
                $page->setVariable('language', $this->config->getLanguageDefault());
            }
        });

        return $this->generatedPages;
    }
}
