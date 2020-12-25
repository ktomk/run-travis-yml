#!/usr/bin/env php
<?php

/*
 * travis plan - rough, experimental outline of matrix/jobs in .travis.yml
 */

use Ktomk\TravisYml\Args;
use Ktomk\TravisYml\EnvVar;
use Ktomk\TravisYml\Language;
use Ktomk\TravisYml\Node;
use Ktomk\TravisYml\TravisYml;

require __DIR__ . '/autoload.php';

Args::create($argv)
    ->env('TRAVIS_YAML_FILE', $defaultFile, '.travis.yml')
    ->optArg(array('-f', '--file'), $file, $defaultFile)
    ->optArg('--run-job', $runJob, '');

$config = Args::loadConfig($file);

$raw = function ($buffer) {
    fwrite(STDOUT, $buffer);
};

# this is pretty experimental (matrix expansion keys only for some very little languages yet...)
$yml = $config->getDocument();
$language = $yml['language'];
$matrixKeys = Language::expansionKeys($language);
!empty($yml['env']['jobs']) && $matrixKeys[] = 'env'; # make support for env in the matrix visible

$jobs = $config->jobStepScripts();
$jobKeys = array_keys($jobs);
$maxLenOfJobKeys = max(array_map('strlen', $jobKeys));
$highlightKeys = array_diff($matrixKeys, array('env'));
$maxLenOfHighlight = 0;
$maxLenOfLabel = 0;
array_map(function ($key) use (&$jobs, &$maxLenOfLabel)
{
    $job = &$jobs[$key];
    $stage = Node::item($job, 'stage', '*');
    $stageName = TravisYml::fmtBuildStageName($stage);
    $name = TravisYml::fmtBuildJobName(Node::item($job, 'name', $stage));
    $stageName === TravisYml::fmtBuildStageName($name) || $name = sprintf('%s: %s', $stageName, $name);
    $maxLenOfLabel = max($maxLenOfLabel, strlen($name));
    $job['label'] = $name;
}, $jobKeys);
array_map(function ($key) use (&$jobs, &$maxLenOfHighlight, $highlightKeys) {
    $job = &$jobs[$key];
    // highlight if key set - beware: different language, different matrix keys on job level
    $highlight = '';
    if (!in_array($key, array('default', 'default'))) {
        foreach ($highlightKeys as $highlightKey) {
            if (isset($job[$highlightKey])) {
                $highlight .= sprintf('%s:%s ', $highlightKey, strtolower(trim($job[$highlightKey])));
            }
        }
    }
    $highlight = rtrim($highlight);
    $maxLenOfHighlight = max($maxLenOfHighlight, strlen($highlight));
    $job['highlight'] = $highlight;
}, $jobKeys);
$labels = array_map(function ($key) use ($maxLenOfLabel, $jobs, $maxLenOfJobKeys, $language, $maxLenOfHighlight) {
    $job = $jobs[$key];

    $label = $job['label'];
    $lang = strtolower(trim(Node::item($job, 'language', $language)));
    $highlight = $job['highlight'] ? '  ' . $job['highlight'] : '';
    $vars = implode(' ', EnvVar::exportMapSequence(Node::item($job, 'env', array())));
    $maxLenOfHighlight && $maxLenOfHighlight+=2;

    return sprintf(
        '%-' . (int)$maxLenOfJobKeys . 's  %-' . (int)$maxLenOfLabel. 's%s%-'.(int)$maxLenOfHighlight .'s  %s',
        $key, $label,
        $lang === $language ? '' : '  ' . $lang,
        $highlight,
        $vars
    );
}, $jobKeys);

$raw(sprintf("language: %s\n", $language));
$raw(sprintf("matrix keys: (%d) %s \n", count($matrixKeys), implode(', ', $matrixKeys)));
$jobsCount = count($jobKeys);
if (0 === $jobsCount) {
    $raw("no run-jobs.\n");
} else {
    $raw(sprintf("run-jobs: (%d)\n", $jobsCount));
    $raw(" - " . implode("\n - ", $labels) . "\n");
}

$raw(sprintf("---\nrun-job: \"%s\" ", $runJob));
$job = Args::runJob($config, $runJob);
$flag = function ($const = null, $flags = 0) {
    (null !== $const) && defined($const) && $flags |= constant($const);
    return $flags;
};
if (function_exists('json_encode')) {
    /** @noinspection PhpComposerExtensionStubsInspection */
    $raw(json_encode(
            $job,
            $flag('JSON_UNESCAPED_SLASHES', $flag('JSON_PRETTY_PRINT'))
        ) . "\n");
} else {
    $raw(print_r($job, true));
}
