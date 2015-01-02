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
            'handler' => new PHPoole\Test(),
            'description' => 'description',
            'short_description' => 'short_description',
        ),
        array(
            'name' => 'init',
            'handler' => new PHPoole\Init(),
            'description' => 'description',
            'short_description' => 'short_description',
        ),
    ),
);