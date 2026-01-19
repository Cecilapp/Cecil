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
use Cecil\Config;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Util;
use Psr\Log\LoggerInterface;

/**
 * Generator abstract class.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    /** @var LoggerInterface */
    protected $logger;

    /** @var PagesCollection */
    protected $generatedPages;

    /**
     * Flexible constructor supporting dependency injection or legacy mode.
     */
    public function __construct(Builder $builder, ?Config $config = null, ?LoggerInterface $logger = null)
    {
        $this->builder = $builder;
        $this->config = $config ?? $builder->getConfig();
        $this->logger = $logger ?? $builder->getLogger();
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
