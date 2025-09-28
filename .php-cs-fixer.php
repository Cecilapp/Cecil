<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$fileHeader =
    <<<'EOF'
    This file is part of Cecil.

    (c) Arnaud Ligny <arnaud@ligny.fr>

    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF
;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'native_function_invocation' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'header_comment' => [
            'header' => $fileHeader,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
        ],
    ])
    ->setFinder(
        (new Finder())
            ->ignoreDotFiles(false)
            ->ignoreVCSIgnored(true)
            ->exclude(['tests/fixtures'])
            ->in(__DIR__)
    )
;
