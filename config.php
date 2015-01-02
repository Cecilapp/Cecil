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
            'name' => 'test',
            'description' => 'description',
            'short_description' => 'short_description',
            'handler' => 'PHPoole\Test',
        ),
        array(
            'name' => 'init',
            'route' => '[<website>]',
            'description' => 'description',
            'short_description' => 'short_description',
            'options_descriptions' => array(
                '<website>' => 'Website path',
            ),
            'defaults' => array(
                'website' => getcwd(), // default to current working directory
            ),
            'handler' => 'PHPoole\Init',
        ),
    ),
);