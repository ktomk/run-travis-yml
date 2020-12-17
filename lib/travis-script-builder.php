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

# stage operands
$stageNames = TravisYml::$customSteps;
if (isset($argv[1]) && '' !== trim($argv[1])) {
    $stages = array_reduce(array_splice($argv, 1), function ($carry, $item) {
        return array_merge($carry, array_filter(preg_split('~[^a-z_]+~i', $item)));
    }, array());
} else {
    $stages = $stageNames;
}
# the original implementations flaw to confluent steps w/ stages
# allows to forward port to actually select job/stage etc.
$steps = TravisYml::filterSteps($stages);

# load .travis.yml file
try {
    $config = TravisYml::openFile($file);
} catch (Exception $e) {
    fprintf(STDERR, "fatal: %s\n", $e->getMessage());
    exit(1);
}

# the original implementations flaw to confluent the config w/ job
# allows to forward port to actual jobs
$jobStepScripts = $config->bareCustomStepScripts();

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
$envGlobal = function(array $env) use ($raw, $cmd) {
    if (!$env['global']) {
        return;
    }
    $globals = $env['global'];
    $raw(sprintf("\necho -e \"\\n\${ANSI_YELLOW}Setting environment variables from %s\${ANSI_RESET}\"\n", '.travis.yml'));
    foreach (EnvVar::exportMapSequence($globals) as $line) {
        $command = sprintf("export %s", $line);
        $cmd($command, false, '');
    }
    $raw("printf '\n'\n");
};
$nameBuildStage = function($name) use ($raw) {
    $raw(sprintf("export TRAVIS_BUILD_STAGE_NAME=%s\n", Lib::quoteArg(ucfirst(strtolower($name)))));
};
$runCustomStep = function ($stage) use ($jobStepScripts, $cmd, $result, &$assert) {
    $assert = in_array($stage, array('setup', 'before_install', 'install', 'before_script', 'before_deploy'), true);
    $fold = $stage !== 'script';
    $commands = array_values($jobStepScripts[$stage]);
    foreach ($commands as $ix => $command) {
        $cmd($command, $fold, sprintf('%s%s', $stage, count($commands) > 1 ? '.' . ($ix + 1) : ''));
        if (!$fold) {
            $result();
        }
    }
};

$raw(file_get_contents(__DIR__ . '/template/header.sh'));
$envGlobal($config->env());
$nameBuildStage('test');
foreach ($steps as $ix => $step) {
    isset($jobStepScripts[$step]) && $runCustomStep($step);
}
$raw(file_get_contents(__DIR__ . '/template/footer.sh'));
