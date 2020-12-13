#!/bin/bash
# action .travis.yml run
# usage: test/action.sh
set -e

export GITHUB_WORKSPACE="${GITHUB_WORKSPACE-.}"
export GITHUB_ACTION_PATH="${GITHUB_ACTION_PATH-.}"
export GITHUB_ENV="./test/github.env"
export GITHUB_REF="refs/heads/test-branch"
export GITHUB_RUN_ID=1
export GITHUB_RUN_NUMBER=123
export GITHUB_SHA=0000000000000000000000000000000000000000
export GITHUB_REPOSITORY=ktomk/run-travis-yml
export GITHUB_SERVER_URL=https://github.com.localhost

export travis_file="${travis_file:-./test/.travis.yml}"
export travis_stages="${travis_stages:-}"


err_report() {
  echo "test-action: errexit on line $(caller)" >&2
}
trap err_report ERR

rm -rf -- "$GITHUB_ENV"

# action script stub as in action.yml

: [1/3] action binding
. "$GITHUB_ACTION_PATH"/lib/binding.sh
: [2/3] register action
reg  allow_failure      '${{ inputs.allow_failure }}'
reg  event_name         '${{ github.event_name }}'
reg  event_number       '${{ github.event.number }}'
reg  repository         '${{ github.repository }}'
reg  travis_file        '${{ inputs.file }}'
reg  travis_stages      '${{ inputs.stages }}'
: [3/3] action
(
  set -euo pipefail
  . "$GITHUB_ACTION_PATH"/lib/action.sh
) && result=$? || result=$?
if [[ -f "$GITHUB_ENV" ]]; then echo "test-action: exported environment file:"; { ls -al -- "$GITHUB_ENV"; cat -- "$GITHUB_ENV"; } | sed 's/^/  /'; fi
printf 'Done. The action exited with %s.\n' "$result"
exit $result
