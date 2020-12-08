#!/bin/sh
set -ex

if command -v composer; then
  composer validate # does not catch that many misconfigurations w/ paths btw.
fi

if command -v shellcheck; then
  shellcheck lib/script/install.sh
  shellcheck test/shell.sh
fi

if command -v misspell; then
  misspell README.md
fi

cd test

rm -f build.sh

../travis-script-builder.php > build.sh

test -f build.sh

grep '"hello world"' build.sh
