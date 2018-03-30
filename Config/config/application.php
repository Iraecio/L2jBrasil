<?php
$composer = [];

if (file_exists(__DIR__ . '/../composer.json'))
    @$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);

$version = isset($composer['version']) ? $composer['version'] : '1.0.0-dev';

define('VERSION', $version);

return ['name' => 'L2jCabir CLI', 'version' => VERSION];
