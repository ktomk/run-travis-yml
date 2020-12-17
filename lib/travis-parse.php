#!/usr/bin/env php
<?php

/*
 * travis parse - show json representation of .travis.yml file
 */

use Ktomk\Pipelines\Lib;
use Ktomk\TravisYml\EnvVar;
use Ktomk\TravisYml\TravisYml;

require __DIR__ . '/autoload.php';

# --file option and argument
$defaultFile = getenv('TRAVIS_YAML_FILE') ?: '.travis.yml';
$file = $defaultFile;
if (isset($argv[1]) && in_array($argv[1], array('-f', '--file'), true)) {
    if (!isset($argv[2])) {
        fprintf(STDERR, "fatal: %s needs an argument\n", $argv[1]);
        exit(1);
    }
    $file = $argv[2];
    array_splice($argv, 1, 2);
}

# load .travis.yml file
try {
    $config = TravisYml::openFile($file);
} catch (Exception $e) {
    fprintf(STDERR, "fatal: %s\n", $e->getMessage());
    exit(1);
}

$raw = function ($buffer) {
    fwrite(STDOUT, $buffer);
};
$flag = function ($const = null, $flags = 0) {
    (null !== $const) && defined($const) && $flags |= constant($const);
    return $flags;
};

$value = $config->getDocument();
if (function_exists('json_encode')) {
    $raw(json_encode(
            $value,
            $flag('JSON_UNESCAPED_SLASHES', $flag('JSON_PRETTY_PRINT'))
        ) . "\n");
} else {
    $raw(print_r($value, true));
}
