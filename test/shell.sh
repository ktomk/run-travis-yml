#!/bin/sh
set -ex

if command -v composer; then
  composer --version
  composer validate # does not catch that many misconfigurations w/ paths btw.
fi

if command -v shellcheck; then
  shellcheck --version
  shellcheck lib/script/install.sh
  shellcheck --shell=bash lib/action.sh
  shellcheck --shell=bash lib/binding.sh
  shellcheck test/shell.sh
fi

if command -v /usr/bin/shellcheck; then
  /usr/bin/shellcheck --version
  /usr/bin/shellcheck lib/script/install.sh
  /usr/bin/shellcheck --shell=bash lib/action.sh
  /usr/bin/shellcheck --shell=bash lib/binding.sh
  /usr/bin/shellcheck test/shell.sh
fi

if command -v misspell; then
  misspell -v
  misspell -error README.md action.yml
fi

: [1] build with default file name
( cd test
  rm -f build.sh
  ../lib/travis-script-builder.php > build.sh
  test -f build.sh
  grep '"hello world"' build.sh
)

: [2] build with "$TRAVIS_YAML_FILE"
rm -f test/build.sh
TRAVIS_YAML_FILE=test/.travis.yml ./lib/travis-script-builder.php > test/build.sh
test -f test/build.sh
grep '"hello world"' test/build.sh

: [3] build with --file
rm -f test/build.sh
./lib/travis-script-builder.php --file test/.travis.yml > test/build.sh
test -f test/build.sh
grep '"hello world"' test/build.sh

: [4] fail with --file
./lib/travis-script-builder.php --file 2>&1  | grep 'fatal: --file needs an argument'

: [5] run stages
test "$(./lib/travis-script-builder.php -f test/.travis.yml "-
" | grep -c '"hello world"')" -eq 0 # script execution
test "$(./lib/travis-script-builder.php -f test/.travis.yml \
  | grep -c '"hello world"')" -eq 1 # script execution
test "$(./lib/travis-script-builder.php -f test/.travis.yml "script
script" | grep -c '"hello world"')" -eq 2 # script executions
test "$(./lib/travis-script-builder.php -f test/.travis.yml "script
foo-script  foo-bar-baz
script" | grep -c '"hello world"')" -eq 3 # script executions

: [6] execute build.sh script
./test/action.sh
