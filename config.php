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
            'short_description' => 'Build a new PHPoole website',
            'description' => 'Build a new PHPoole website in <path> if provided',
            'options_descriptions' => array(
                '<path>'  => 'Website path',
                '--force' => 'Override if already exist',
            ),
            'defaults' => array(
                'path' => getcwd(),
            ),
            'handler' => 'PHPoole\Command\Init',
        ),
        array(
            'name' => 'generate',
            'route' => '[<path>]',
            'short_description' => 'Generate static files',
            'defaults' => array(
                'path' => getcwd(),
            ),
            'handler' => 'PHPoole\Command\Generate',
        ),
        array(
            'name' => 'serve',
            'route' => '[<path>]',
            'short_description' => 'Start built-in web server',
            'defaults' => array(
                'path' => getcwd(),
            ),
            'handler' => 'PHPoole\Command\Serve',
        ),
        array(
            'name' => 'list',
            'short_description' => 'Lists content',
        ),
    ),
);