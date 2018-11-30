<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__.'/src');

return new Sami($iterator, [
    'title'     => 'Cecil API',
    'build_dir' => __DIR__.'/docs/api/%version%',
    'cache_dir' => __DIR__.'/docs/api/cache/%version%',
]);
