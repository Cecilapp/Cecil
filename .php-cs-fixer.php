<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$finder = (new PhpCsFixer\Finder())
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->exclude(['tests/fixtures'])
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        'native_function_invocation' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'header_comment' => ['header' => <<<'EOF'
            This file is part of Cecil.

            Copyright (c) Arnaud Ligny <arnaud@ligny.fr>

            For the full copyright and license information, please view the LICENSE
            file that was distributed with this source code.
            EOF],
    ])
    ->setFinder($finder)
;
