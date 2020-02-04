<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

/**
 * Class DefaultPages.
 */
class DefaultPages extends VirtualPages
{
    protected $configKey = 'defaultpages';

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        parent::generate();
    }
}
