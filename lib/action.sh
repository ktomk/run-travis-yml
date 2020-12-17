# run .travis.yml

### travis environment variables

gh_var TRAVIS                                  true
gh_var CI                                      true
gh_var CONTINUOUS_INTEGRATION                  true
gh_var PAGER                                   "cat"
gh_var HAS_JOSH_K_SEAL_OF_APPROVAL             true
gh_var HAS_ANTARES_THREE_LITTLE_FRONZIES_BADGE true  #  n/a in travis-build master; noted for soft-deprecation
gh_var TRAVIS_ALLOW_FAILURE                    false allow_failure
gh_var TRAVIS_APP_HOST                         "$(hostname)"
gh_var TRAVIS_BUILD_DIR                        "$GITHUB_WORKSPACE"
gh_var TRAVIS_BUILD_ID                         "$GITHUB_RUN_ID"
gh_var TRAVIS_BUILD_NUMBER                     "$GITHUB_RUN_NUMBER"
gh_var TRAVIS_BUILD_WEB_URL                    "$GITHUB_SERVER_URL/$GITHUB_REPOSITORY/actions/runs/$GITHUB_RUN_ID"
gh_var TRAVIS_BRANCH                           "$(gh_refname "${GITHUB_BASE_REF:-"${GITHUB_REF:?"either GITHUB_BASE_REF or GITHUB_REF expected"}"}")"
gh_var TRAVIS_COMMIT                           "$GITHUB_SHA"
gh_var TRAVIS_COMMIT_MESSAGE                   "$(test -d .git && git log --format=%B -n 1 | head -c 32768)"
gh_var TRAVIS_EVENT_TYPE                       "$(gh_eventname "${event_name?"event_name expected"}")"
gh_var TRAVIS_PULL_REQUEST                     false event_number
gh_var TRAVIS_REPO_SLUG                        '' repository
gh_var TRAVIS_SUDO                             "$(gh_sudo)"
gh_var TRAVIS_YAML_FILE                        '.travis.yml' travis_file

### travis environment table
#
# travis default environment variables (long lists following)
#
# link: <https://docs.travis-ci.com/user/environment-variables/#default-environment-variables>
# file: travis-ci/travis-build/lib/travis/build/env/builtin.rb
#
# excluded:
#   TRAVIS_BUILD_STAGE_NAME (internal)
#   TRAVIS_TEST_RESULT      (internal)
gh_env \
"""
TRAVIS
CI
CONTINUOUS_INTEGRATION
PAGER
HAS_JOSH_K_SEAL_OF_APPROVAL
HAS_ANTARES_THREE_LITTLE_FRONZIES_BADGE
TRAVIS_ALLOW_FAILURE
TRAVIS_APP_HOST
TRAVIS_BRANCH
TRAVIS_BUILD_DIR
TRAVIS_BUILD_ID
TRAVIS_BUILD_NUMBER
TRAVIS_BUILD_WEB_URL
TRAVIS_COMMIT
TRAVIS_COMMIT_MESSAGE
TRAVIS_COMMIT_RANGE
TRAVIS_COMPILER
TRAVIS_DEBUG_MODE
TRAVIS_DIST
TRAVIS_EVENT_TYPE
TRAVIS_JOB_ID
TRAVIS_JOB_NAME
TRAVIS_JOB_NUMBER
TRAVIS_JOB_WEB_URL
TRAVIS_OS_NAME
TRAVIS_CPU_ARCH
TRAVIS_OSX_IMAGE
TRAVIS_PULL_REQUEST
TRAVIS_PULL_REQUEST_BRANCH
TRAVIS_PULL_REQUEST_SHA
TRAVIS_PULL_REQUEST_SLUG
TRAVIS_REPO_SLUG
TRAVIS_SECURE_ENV_VARS
TRAVIS_SUDO
TRAVIS_TAG

TRAVIS_DART_VERSION
TRAVIS_GO_VERSION
TRAVIS_HAXE_VERSION
TRAVIS_JDK_VERSION
TRAVIS_JULIA_VERSION
TRAVIS_NODE_VERSION
TRAVIS_OTP_RELEASE
TRAVIS_PERL_VERSION
TRAVIS_PHP_VERSION
TRAVIS_PYTHON_VERSION
TRAVIS_R_VERSION
TRAVIS_RUBY_VERSION
TRAVIS_RUST_VERSION
TRAVIS_SCALA_VERSION

TRAVIS_MARIADB_VERSION

TRAVIS_XCODE_SDK
TRAVIS_XCODE_SCHEME
TRAVIS_XCODE_PROJECT
TRAVIS_XCODE_WORKSPACE

TRAVIS_YAML_FILE
"""

### write/run .travis.yml, process results and finish

gh_parse
gh_plan
gh_build_run
gh_allow_failure

gh_export TRAVIS_ALLOW_FAILURE
gh_export TRAVIS_TEST_RESULT
gh_export TRAVIS_YAML_FILE

gh_terminate # done.
