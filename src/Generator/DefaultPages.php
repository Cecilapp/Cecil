<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

/**
 * DefaultPages generator class.
 *
 * This class extends the VirtualPages generator to create default pages
 * based on the configuration key 'pages.default'.
 * It is used to generate pages that are not explicitly defined in the content.
 * The pages are generated based on the configuration settings and can include
 * common pages like '404', '500', 'about', etc.
 */
class DefaultPages extends VirtualPages
{
    protected $configKey = 'pages.default';

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        parent::generate();
    }
}
