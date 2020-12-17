#!/usr/bin/env php
<?php

/*
 * travis plan - rough, experimental outline of matrix/jobs in .travis.yml
 */

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

# this is pretty experimental (add exclude to make this visible, matrix keys only for some very little languages)
$matrixKeys = $config->language()->matrixKeys();
$jobNames = array_keys($config->jobStepScripts());
$raw(sprintf("matrix keys (%d): %s # language: %s\n", count($matrixKeys), implode(', ', $matrixKeys), $config->language()->name()));
$raw(sprintf("jobs (%d):\n", count($jobNames)));
$raw(" - " . implode("\n - ", $jobNames) . "\n");
