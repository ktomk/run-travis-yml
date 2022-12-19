<?php

/*
 * run-travis-yml - compinst.php - composer version installer
 */

$hashes = array(
    '2.0.12' => array('sha256', '82ea8c1537cfaceb7e56f6004c7ccdf99ddafce7237c07374d920e635730a631'),
);
$versionUrlPattern = 'https://getcomposer.org/download/%s/composer.phar';

if ($argc < 3) {
    $numbers = array('none', 'one');
    error_log(sprintf('fatal: insufficient arguments, need two, got %s.', $numbers[$argc-1]));
    error_log(sprintf('usage: %s <version> <target>', $argv[0]));
    exit(1);
}

list(, $version, $target) = $argv;

if (is_dir($target)) {
    error_log(sprintf('fatal: refusing to target a directory: %s', var_export($target, true)));
    exit(1);
}
if (is_link($target)) {
    error_log(sprintf('fatal: refusing to resolve symbolic link for target: %s', var_export($target, true)));
    exit(1);
}

// FIXME(tk): path-check $target
$charset = implode('', array(
    '-.',
    '/',
    '0123456789',
    'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    '\\',
    '_',
    'abcdefghijklmnopqrstuvwxyz',
));
$full = strlen($target);
$check = strspn($target, $charset);
if ($full !== $check) {
    error_log(sprintf(
        'fatal: cowardly refusing target %s at offset %d: %s%s',
        var_export($target, true),
        $check,
        0 === $check ? '' : '...',
        var_export(substr($target, $check), true)
    ));
    exit(1);
}

$targetBasename = basename($target);
if (in_array($targetBasename, array('', '.', '..'), true)) {
    error_log(sprintf('fatal: refusing target basename: %s', var_export($targetBasename, true)));
    exit(1);
}
$targetDirname = dirname($target);
$targetDirRealPath = realpath($targetDirname);
if (false === $targetDirRealPath || !is_dir($targetDirname) || is_link($targetDirname)) {
    error_log(sprintf('fatal: not a directory: %s', var_export($targetDirname, true)));
    exit(1);
}
if ($targetDirname !== $targetDirRealPath) {
    error_log(sprintf('%s: target directory is %s', basename($argv[0]), var_export($targetDirRealPath, true)));
}
$targetPathName = $targetDirRealPath . '/' . $targetBasename;

if (!isset($hashes[$version])) {
    error_log(sprintf('%s: unknown version: %s', $argv[0], $version));
    exit(1);
}
list($hashAlgo, $versionHash) = $hashes[$version];

if (file_exists($targetPathName)) {
    $targetPreCheckHash = hash_file($hashAlgo, $targetPathName);
    if (false === $targetPreCheckHash) {
        error_log(sprintf('fatal: failed to acquire hash from existing target %s', var_export($target, true)));
        exit(1);
    }
    if ($targetPreCheckHash === $versionHash) {
        printf("%s  %s%s", $targetPreCheckHash, $target, PHP_EOL);
        exit(0);
    }
    // FIXME(tk): do not remove the file if download is not possible
    if (!unlink($targetPathName) && file_exists($targetPathName)) {
        error_log(sprintf('fatal: failed to invalidate non-matching target %s', var_export($target, true)));
        exit(1);
    }
}

$url = sprintf($versionUrlPattern, $version);

$urlHandle = @fopen($url, 'rb');
if (!$urlHandle) {
    error_log(sprintf('fatal: failed to open %s', var_export($url, true)));
    exit(1);
}

$hashContext = hash_init($hashAlgo);
$tempResource = tmpfile();
$peek = stream_copy_to_stream($urlHandle, $tempResource, 512);
if (false === $peek) {
    error_log(sprintf('fatal: failed to read initial bytes %s', var_export($url, true)));
    exit(1);
}

$rest = stream_copy_to_stream($urlHandle, $tempResource);
if (false === $rest) {
    error_log(sprintf('fatal: failed to read continuous bytes %s', var_export($url, true)));
    exit(1);
}
if (!feof($urlHandle)) {
    error_log(sprintf('fatal: failed to read finish read of %s', var_export($url, true)));
    exit(1);
}
if (!fclose($urlHandle)) {
    error_log(sprintf('fatal: failed to close read handle of %s', var_export($url, true)));
    exit(1);
}
$totalBytes = $peek + $rest;
error_log(sprintf(
    '%s: downloaded %s byte%s from %s',
    basename($argv[0]),
    number_format($totalBytes, 0, '%', '_'),
    1 < $totalBytes ? 's' : '',
    var_export($url, true)
));

rewind($tempResource);
$hashBytes = hash_update_stream($hashContext, $tempResource);
if ($hashBytes !== $totalBytes) {
    fclose($tempResource);
    error_log(sprintf(
        'fatal: hash bytes mismatch from download bytes: %s (offset by: %s)',
        number_format($hashBytes, 0, '%', '_'),
        number_format($hashBytes - $totalBytes, 0, '%', '_')
    ));
    exit(1);
}
$tempHash = hash_final($hashContext);
error_log(sprintf('info: download hash %s', $tempHash));
if ($tempHash !== $versionHash) {
    fclose($tempResource);
    error_log(sprintf('fatal: download hash mismatch: %s not required %s', $tempHash, $versionHash));
    exit(1);
}
rewind($tempResource);

$hashContext = hash_init($hashAlgo);
$targetResource = fopen($targetPathName, 'w+b');
$copyBytes = stream_copy_to_stream($tempResource, $targetResource);
if ($copyBytes !== $totalBytes) {
    fclose($tempResource);
    fclose($targetResource);
    error_log(sprintf(
        'fatal: copy bytes mismatch from download bytes: %s (offset by: %s)',
        number_format($copyBytes, 0, '%', '_'),
        number_format($copyBytes - $totalBytes, 0, '%', '_')
    ));
    exit(1);
}
rewind($targetResource);
$hashBytes = hash_update_stream($hashContext, $targetResource);
if ($hashBytes !== $totalBytes) {
    error_log(sprintf(
        'fatal: hash bytes mismatch from copy and download bytes: %s (offset by: %s)',
        number_format($hashBytes, 0, '%', '_'),
        number_format($hashBytes - $totalBytes, 0, '%', '_')
    ));
    exit(1);
}
$targetHash = hash_final($hashContext);
error_log(sprintf('info: target hash %s', $targetHash));
if ($targetHash !== $versionHash) {
    error_log(sprintf('fatal: target hash mismatch: %s not required %s', $targetHash, $versionHash));
    exit(1);
}

fclose($tempResource);
fclose($targetResource);
