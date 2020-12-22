# run .travis.yml
#
# action.sh - run .travis.yml github action command
#

# shellcheck source=environment.sh
. "$GITHUB_ACTION_PATH/environment.sh"

### write/run .travis.yml, process results and finish

gh_parse
gh_plan
gh_build_run
gh_allow_failure

gh_export TRAVIS_ALLOW_FAILURE
gh_export TRAVIS_TEST_RESULT
gh_export TRAVIS_YAML_FILE

gh_terminate # done.
