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

namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

/**
 * Class Test.
 */
class Test extends AbstractPostProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process(Page $page, string $output, string $format): string
    {
        if ($format == 'html') {
            $test = \sprintf('<meta name="test" content="TEST" />');
            $output = preg_replace_callback('/([[:blank:]]*)(<\/head>)/i', function ($matches) use ($test) {
                return str_repeat($matches[1] ?: ' ', 2) . $test . "\n" . $matches[1] . $matches[2];
            }, $output);
        }

        return $output;
    }
}
