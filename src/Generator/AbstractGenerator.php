<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Util;

/**
 * Abstract class AbstractGenerator.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /** @var Builder */
    protected $builder;
    /** @var \Cecil\Config */
    protected $config;
    /** @var PagesCollection */
    protected $pagesCollection;
    /** @var PagesCollection */
    protected $generatedPages;
    /** @var \Closure */
    protected $messageCallback;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        // Create new empty collection
        $this->generatedPages = new PagesCollection('generator-'.Util::formatClassName($this, ['lowercase' => true]));
    }

    /**
     * @param PagesCollection $pagesCollection
     * @param \Closure        $messageCallback
     *
     * @return PagesCollection
     */
    public function runGenerate(PagesCollection $pagesCollection, \Closure $messageCallback): PagesCollection
    {
        $this->pagesCollection = $pagesCollection;
        $this->messageCallback = $messageCallback;

        $this->generate();

        return $this->generatedPages;
    }
}
