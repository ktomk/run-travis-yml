#!/usr/bin/env php
<?php

/*
 * travis script builder - ease migration from travis.yml to other platforms
 *
 * a minimal, build.sh port from travis-build
 *
 * usage: ./travis-script-builder.php [--file <travis-yml-path>] [<stage>...]
 *
 * environment:
 *     TRAVIS_YAML_FILE    path to .travis.yml file
 *
 * https://github.com/travis-ci/travis-build/tree/master/lib/travis
 * https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/stages.rb
 * https://github.com/travis-ci/travis-build/blob/73f74a94957f73eb54dc821f80c0c85ad8f8aab7/lib/travis/build/script/templates/header.sh
 */

use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Yaml\Yaml as Yaml;

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

# stage operands
$stageNames = array('setup', 'before_install', 'install', 'before_script', 'script', 'after_script', 'before_deploy');
if (isset($argv[1]) && '' !== trim($argv[1])) {
    $stages = array_reduce(array_splice($argv, 1), function ($carry, $item) {
        return array_merge($carry, array_filter(preg_split('~[^a-z_]+~i', $item)));
    }, array());
} else {
    $stages = $stageNames;
}

# open .travis.yml file
try {
    $config = Yaml::file($file);
} catch (Exception $e) {
    fprintf(STDERR, "fatal: %s\n", $e->getMessage());
    exit(1);
}

# render bash.sh script (rest)
$assert = false;
$raw = function($buffer) {
    fwrite(STDOUT, $buffer);
};
$head1 = function($buffer) {
    $lines = explode("\n", $buffer, 2) + array("", null);
    return $lines[0];
};
$label = function($text) {
    $verticalFourDots = "\xE2\x81\x9E";
    $squareFourDots = "\xE2\xB8\xAC";
    $bullet = "\xE2\x80\xA2";
    $punct = $bullet;
    $nbsp = "\xC2\xA0";
    return sprintf("\033[34m%s%s\033[0m\033[34m%s\033[0m",
        "$punct$nbsp", $text,"$nbsp$punct");
};
$cmd = function($command, $fold, $foldName) use (&$assert, $raw, $head1, $label) {
    $echo = '--echo';
    $args = array(
        $command,
        $assert ? '--assert' : null,
        array(&$echo, '--timing'),
    );
    if ($fold) {
        $echo = '--echonp2';
        $foldName = sprintf("%s %s", $head1($command), $label($foldName));
        $raw(sprintf("%s\n", Lib::cmd('travis_fold', array('start', $foldName))));
    }
    $raw(sprintf("2>&1 %s\n", Lib::cmd('travis_cmd', $args)));
    $fold && $raw(sprintf("%s\n", Lib::cmd('travis_fold', array('end', $foldName))));
};
$result = function() use ($raw) {
    $raw(sprintf("travis_result \$?\n"));
};
$name = function($name) use ($raw) {
    $raw(sprintf("export TRAVIS_BUILD_STAGE_NAME=%s\n", Lib::quoteArg($name)));
};

$runCustomStage = function ($stage) use ($config, $name, $cmd, $result, &$assert) {
    $assert = in_array($stage, array('setup', 'before_install', 'install', 'before_script', 'before_deploy'), true);
    $fold = $stage !== 'script';
    $cmds = array_values($config[$stage]);
    $name($stage);
    foreach ($cmds as $ix => $command) {
        $cmd($command, $fold, sprintf('%s%s', $stage, count($cmds) > 1 ? '.' . ($ix + 1) : ''));
        if (!$fold) {
            $result();
        }
    }
};

$raw(file_get_contents(__DIR__ . '/template/header.sh'));
foreach ($stages as $ix => $stage) {
    isset($config[$stage]) && $runCustomStage($stage);
}
$raw(file_get_contents(__DIR__ . '/template/footer.sh'));
