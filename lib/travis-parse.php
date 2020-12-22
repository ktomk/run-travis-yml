#!/usr/bin/env php
<?php

/*
 * travis parse - show json representation of .travis.yml file
 */

use Ktomk\TravisYml\Args;
use Ktomk\TravisYml\TravisYml;

require __DIR__ . '/autoload.php';

Args::create($argv)
    ->env('TRAVIS_YAML_FILE', $defaultFile, '.travis.yml')
    ->optArg(array('-f', '--file'), $file, $defaultFile);

$config = Args::loadConfig($file);

$raw = function ($buffer) {
    fwrite(STDOUT, $buffer);
};
$flag = function ($const = null, $flags = 0) {
    (null !== $const) && defined($const) && $flags |= constant($const);
    return $flags;
};

$value = $config->getDocument();
if (function_exists('json_encode')) {
    /** @noinspection PhpComposerExtensionStubsInspection */
    $raw(json_encode(
            $value,
            $flag('JSON_UNESCAPED_SLASHES', $flag('JSON_PRETTY_PRINT'))
        ) . "\n");
} else {
    $raw(print_r($value, true));
}
