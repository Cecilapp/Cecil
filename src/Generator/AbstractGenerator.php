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

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Util;

/**
 * Generator abstract class.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /** @var Builder */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /** @var PagesCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        // Creates a new empty collection
        $this->generatedPages = new PagesCollection('generator-' . Util::formatClassName($this, ['lowercase' => true]));
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
