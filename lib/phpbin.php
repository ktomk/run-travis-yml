<?php

/*
 * run-travis-yml - php bin setup
 */

if ($argc < 3) {
    $numbers = array('none', 'one');
    error_log(sprintf('fatal: insufficient arguments, need at least two, got %s.', $numbers[$argc-1]));
    error_log(sprintf('usage: %s <command> <target> [<args>...]', $argv[0]));
    exit(1);
}
list(, $command, $target) = $argv;
$args = array_slice($argv, 3);

$buffer = array("#!/bin/sh\n");
$buffer[] = sprintf(
    "%s %s \"\$@\"\n",
    $command,
    implode(' ', array_map('escapeshellarg', $args))
);

file_put_contents($target, $buffer);
chmod($target, 0755);
