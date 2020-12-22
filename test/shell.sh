#!/bin/sh
set -ex

####
target_shellcheck() {
  "${1:-shellcheck}" --version
  "${1:-shellcheck}" lib/script/install.sh test/shell.sh
  "${1:-shellcheck}" --shell=bash \
      action.sh \
      environment.sh \
      lib/binding.sh \
      extra/plan/action.sh \
      ;
}

if command -v composer; then
  composer --version
  composer validate # does not catch that many misconfigurations w/ paths btw.
fi

if command -v shellcheck; then
  target_shellcheck shellcheck
fi

if command -v /usr/bin/shellcheck; then
  target_shellcheck /usr/bin/shellcheck
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

: [6] execute build.sh script from test/.travis.yml
# shellcheck disable=SC2069
./test/action.sh 2>&1 >/dev/null

: [7] standard action has exit status 1
# shellcheck disable=SC2069,SC2015
! travis_file=.travis.yml ./test/action.sh >/dev/null

: [8] standard action must not fail with allow_failure
travis_file=.travis.yml allow_failure=true ./test/action.sh >/dev/null

: [9] standard action must not fail with dry_run_job
travis_file=.travis.yml dry_run_job=true ./test/action.sh >/dev/null

: [10 extra/plan action
# shellcheck disable=SC2069
action_yml=extra/plan/action.yml ./test/action.sh 2>&1 >/dev/null

: [11 extra/plan action fails on invalid run_job
# shellcheck disable=SC2069
! action_yml=extra/plan/action.yml run_job=bogus ./test/action.sh 2>&1 >/dev/null

: [12 extra/plan action run_job named jobs
# shellcheck disable=SC2069
action_yml=extra/plan/action.yml travis_file=test/file/n98-magerun.travis.yml run_job="Bash Autocompletion" ./test/action.sh 2>&1 >/dev/null
