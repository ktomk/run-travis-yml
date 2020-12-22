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
use Ktomk\TravisYml\Args;
use Ktomk\TravisYml\EnvVar;
use Ktomk\TravisYml\Node;
use Ktomk\TravisYml\TravisYml;

require __DIR__ . '/autoload.php';

Args::create($argv)
    ->env('TRAVIS_YAML_FILE', $defaultFile, '.travis.yml')
    ->optArg(array('-f', '--file'), $file, $defaultFile)
    ->optArg(array('--run-job'), $runJob, '')
    ->optFlag(array('--dry-run'), $dryRun)
    ->opStage($stages, TravisYml::$customSteps);

# the original implementations flaw to confluent steps w/ stages
# allows to forward port to actually select job/stage etc.
$steps = TravisYml::filterSteps($stages);

# load .travis.yml file
$config = Args::loadConfig($file);

# the original implementations flaw to confluent the config w/ job
# allows to forward port to actual jobs
$job = Args::runJob($config, $runJob);

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
$cmd = function($command, $fold, $foldName, $dryRun = false, $doTiming = true) use (&$assert, $raw, $head1, $label) {
    $echo = '--echo';
    $args = array(
        $command,
        $assert ? '--assert' : null,
        array(&$echo),
        $doTiming ? '--timing' : null,
        $dryRun ? '--noexec' : null,
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
$envJob = function(array $job) use ($raw, $cmd) {
    if (empty($job['env'])) {
        return;
    }
    $env = $job['env'];
    $raw(sprintf("\necho -e \"\\n\${ANSI_YELLOW}Setting environment variables from %s\${ANSI_RESET}\"\n", '.travis.yml'));
    foreach (EnvVar::exportMapSequence($env) as $var => $line) {
        $command = sprintf("export %s", $line);
        if (false !== $val = getenv($var)) {
            $command = sprintf('# already set: %s=%s ; in .travis.yml: %s', $var, $val, $line);
        }
        $cmd($command, false, '', false, false);
    }
    $raw("printf '\n'\n");
};
$nameBuildStage = function(array $job) use ($raw) {
    $name = Node::item($job, 'stage', 'test');
    $raw(sprintf("export TRAVIS_BUILD_STAGE_NAME=%s\n", Lib::quoteArg(TravisYml::fmtBuildStageName($name))));
};
$runCustomStep = function ($stage, $dryRun = false) use ($job, $cmd, $result, &$assert) {
    $assert = in_array($stage, array('setup', 'before_install', 'install', 'before_script', 'before_deploy'), true);
    $fold = $stage !== 'script';
    $commands = array_values($job[$stage]);
    foreach ($commands as $ix => $command) {
        $cmd($command, $fold, sprintf('%s%s', $stage, count($commands) > 1 ? '.' . ($ix + 1) : ''), $dryRun);
        if (!$fold) {
            $result();
        }
    }
};

$raw(file_get_contents(__DIR__ . '/template/header.sh'));
$envJob($job);
$nameBuildStage($job);
foreach ($steps as $ix => $step) {
    isset($job[$step]) && $runCustomStep($step, $dryRun);
}
$raw(file_get_contents(__DIR__ . '/template/footer.sh'));
