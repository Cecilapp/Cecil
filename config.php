<?php

$version = '2.0.0-dev';
if (file_exists(__DIR__ . '/composer.json')) {
    @$composer = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
    if (isset($composer['version'])) {
        $version = $composer['version'];
    }
}
define('VERSION', $version);

return array(
    'name'    => 'PHPoole',
    'version' => VERSION,
    'routes'  => array(
        array(
            'name' => 'init',
            'route' => '[<path>] [--force]',
            'description' => 'Build a new PHPoole website in <path> if provided',
            'short_description' => 'Build a new PHPoole website',
            'options_descriptions' => array(
                '<path>' => 'Website path',
            ),
            'defaults' => array(
                'path' => getcwd(), // default to current working directory
            ),
            'handler' => 'PHPoole\Console\Init',
        ),
        array(
            'name' => 'generate',
            'short_description' => 'Generate static files',
        ),
        array(
            'name' => 'serve',
            'short_description' => 'Start built-in web server',
        ),
        array(
            'name' => 'list',
            'short_description' => 'Lists content',
        ),
    ),
);