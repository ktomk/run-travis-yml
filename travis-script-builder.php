#!/usr/bin/env php
<?php

/*
 * travis script builder - ease migration from travis.yml to other platforms
 *
 * just a minimal, quick port
 *
 * https://github.com/travis-ci/travis-build/tree/master/lib/travis
 * https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/stages.rb
 * https://github.com/travis-ci/travis-build/blob/73f74a94957f73eb54dc821f80c0c85ad8f8aab7/lib/travis/build/script/templates/header.sh
 */

use Ktomk\Pipelines\Lib;
use Ktomk\Pipelines\Yaml\Yaml as Yaml;

require __DIR__ . '/lib/autoload.php';

$file = getenv('INPUT_FILE') ?: '.travis.yml';

$stageNames = array('setup', 'before_install', 'install', 'before_script', 'script', 'after_script', 'before_deploy');

if (!isset($argv[1])) {
    $argv = array_merge($argv, $stageNames);
}

try {
    $config = Yaml::file($file);
} catch (Exception $e) {
    fprintf(STDERR, "fatal: %s\n", $e->getMessage());
    exit(1);
}

$assert = false;
$raw = function($buffer) {
    fwrite(STDOUT, $buffer);
};
$cmd = function($command, $fold, $foldName) use (&$assert, $raw) {
    $args = array(
        $command,
        $assert ? '--assert' : null,
        array('--echo', '--timing'),
    );
    $fold && $raw(sprintf("%s\n", Lib::cmd('travis_fold', array('start', $foldName))));
    $raw(sprintf("%s\n", Lib::cmd('travis_cmd', $args)));
    $fold && $raw(sprintf("%s\n", Lib::cmd('travis_fold', array('end', $foldName))));
};
$result = function() use ($raw) {
    $raw(sprintf("travis_result \$?\n"));
};

$runCustomStage = function ($stage) use ($config, $cmd, $result, &$assert) {
    $assert = in_array($stage, array('setup', 'before_install', 'install', 'before_script', 'before_deploy'), true);
    $fold = $stage !== 'script';
    $cmds = array_values($config[$stage]);
    foreach ($cmds as $ix => $command) {
        $cmd($command, $fold, sprintf('%s%s', $stage, count($cmds) > 1 ? '.' . ($ix + 1) : ''));
        if (!$fold) {
            $result();
        }
    }
};

$raw(file_get_contents(__DIR__ . '/lib/template/header.sh'));
foreach ($argv as $ix => $stage) {
    if (0 === $ix) continue;
    isset($config[$stage]) && $runCustomStage($stage);
}
$raw(file_get_contents(__DIR__ . '/lib/template/footer.sh'));
