# run .travis.yml
#
# action.sh - plan .travis.yml run github action command
#

# shellcheck source=environment.sh
. "$GITHUB_ACTION_PATH/environment.sh"

### extra/plan action

gh_parse
gh_close_export

"$GITHUB_ACTION_PATH/lib/travis-plan.php" \
  --file "$TRAVIS_YAML_FILE" --run-job "${run_job:-}"

# shellcheck disable=SC2034
dry_run_job=true
gh_build_run

gh_export TRAVIS_YAML_FILE
gh_close_export
