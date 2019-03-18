<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Config;
use Cecil\Util;

/**
 * Abstract class AbstractGenerator.
 */
abstract class AbstractGenerator implements GeneratorInterface
{
    /* @var Config */
    protected $config;
    /* @var PagesCollection */
    protected $pagesCollection;
    /* @var $messageCallback */
    protected $messageCallback;
    /* @var PagesCollection */
    protected $generatedPages;

    /**
     * {@inheritdoc}
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
