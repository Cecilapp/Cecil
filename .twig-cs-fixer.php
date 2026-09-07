<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());
$ruleset->removeRule(TwigCsFixer\Rules\Whitespace\BlankEOFRule::class);
$ruleset->removeRule(TwigCsFixer\Rules\Function\NamedArgumentSeparatorRule::class);

$config = new TwigCsFixer\Config\Config();
$config->setCacheFile('.twig-cs-fixer.cache');
$config->setRuleset($ruleset);

return $config;
