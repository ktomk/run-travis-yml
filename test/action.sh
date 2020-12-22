#!/usr/bin/env bash
# action .travis.yml run
# usage: test/action.sh
if test "$BASH" = "" || "$BASH" -uc "a=();true \"\${a[@]}\"" 2>/dev/null
    then set -euo pipefail; else set -eo pipefail; fi
shopt -s nullglob globstar

export GITHUB_WORKSPACE="${GITHUB_WORKSPACE-.}"
export GITHUB_ACTION_PATH="${GITHUB_ACTION_PATH-.}"
export GITHUB_ENV="./test/github.env"
export GITHUB_REF="refs/heads/test-branch"
export GITHUB_RUN_ID=1
export GITHUB_RUN_NUMBER=123
export GITHUB_SHA=0000000000000000000000000000000000000000
export GITHUB_REPOSITORY=dr/run-yml
export GITHUB_SERVER_URL=https://localhost

run_job="${run_job:- }" # single space intended
dry_run_job="${dry_run_job:-false}"
travis_file="${travis_file:-${TRAVIS_YAML_FILE-./test/.travis.yml}}"
travis_steps="${travis_steps:- }" # single space intended
action_yml="${action_yml:-./action.yml}" # actual action.yml used as template for registration
test -f "$action_yml"; test -r "$action_yml"; test -s "$action_yml"
action_dir="$(dirname "$action_yml")"

err_report() {
  echo "test-action: errexit on line $(caller)" >&2
}
trap err_report ERR

rm -rf -- "$GITHUB_ENV"

# action script stub as in action.yml

: [1/3] action binding
. "$GITHUB_ACTION_PATH/lib/binding.sh"
# : [2/3] register action
# eval the registration segment from action.yml (: [2/3] to : [3/3]) replacing github action context expressions
# as they are not valid bash ("${{ ... }}") by the literal string of the expression ('${{ ... }}')
eval "$(sed -n '/: \[2\/3\]/,/: \[3\/3\]/ { s/"\(\${{ .* }}\)"/'\''\1'\''/g ; p }' "$action_yml")"
# : [3/3] action
# for test, all internal environment parameters need to be exported to make the test exit safe in a subshell.
eval "$(sed -n '/: \[2\/3\]/,/: \[3\/3\]/ s/^.* reg  \([^ ]\+\) .*$/export \1/p' "$action_yml")"
# export from binding.sh for subshell
export -f gh_travis_result_error
(
  # shellcheck disable=SC2034
  TRAVIS_YAML_ERROR_COUNT=0
  . "$action_dir/action.sh"
) && result=$? || result=$?
if [[ -f "$GITHUB_ENV" ]]; then echo "test-action: exported environment file:"; { ls -al -- "$GITHUB_ENV"; cat -- "$GITHUB_ENV"; } | sed 's/^/  /'; fi
printf 'Done. The action exited with %s.\n' "$result"
exit $result
