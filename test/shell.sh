#!/bin/sh
set -ex

if command -v composer; then
  composer --version
  composer validate # does not catch that many misconfigurations w/ paths btw.
fi

if command -v shellcheck; then
  shellcheck --version
  shellcheck lib/script/install.sh
  shellcheck test/shell.sh
fi

if command -v misspell; then
  misspell -v
  misspell README.md
fi

#  [1] build with default file name
(
  cd test

  rm -f build.sh
  ../lib/travis-script-builder.php > build.sh
  test -f build.sh
  grep '"hello world"' build.sh
)

#  [2] build with $INPUT_FILE
rm -f test/build.sh
INPUT_FILE=test/.travis.yml ./lib/travis-script-builder.php > test/build.sh
test -f test/build.sh
grep '"hello world"' test/build.sh
