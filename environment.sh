# run .travis.yml
#
# environment.sh - prepare TRAVIS_* environment
#

# add bin-shims first in path
if ! hash phpenv 2>/dev/null; then
  PATH="$GITHUB_ACTION_PATH/lib/bin-shims:$PATH"
  export PATH
fi
# set shell to bash
SHELL="${SHELL-$BASH}"
export SHELL

### travis environment variables

gh_var TRAVIS                                  true
gh_var CI                                      true
gh_var CONTINUOUS_INTEGRATION                  true
gh_var DEBIAN_FRONTEND                         noninteractive
gh_var HAS_JOSH_K_SEAL_OF_APPROVAL             true
gh_var HAS_ANTARES_THREE_LITTLE_FRONZIES_BADGE true  #  n/a in travis-build master; noted for soft-deprecation
gh_var PAGER                                   "cat"
gh_var LANG                                    "en_US.UTF-8"
gh_var LC_ALL                                  "en_US.UTF-8"
gh_var RAILS_ENV                               "test"
gh_var RACK_ENV                                "test"
gh_var MERB_ENV                                "test"
gh_var JRUBY_OPTS                              "--server -Dcext.enabled=false -Xcompile.invokedynamic=false"

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
# link: <https://github.com/travis-ci/travis-build/blob/master/lib/travis/build/env/builtin.rb>
#
# excluded:
#   TRAVIS_BUILD_STAGE_NAME (set by build.sh)
#   TRAVIS_TEST_RESULT      (set by build.sh)
gh_env \
"""
TRAVIS
CI
CONTINUOUS_INTEGRATION
DEBIAN_FRONTEND
HAS_JOSH_K_SEAL_OF_APPROVAL
HAS_ANTARES_THREE_LITTLE_FRONZIES_BADGE
USER
HOME
PAGER
LANG
LC_ALL
RAILS_ENV
RACK_ENV
MERB_ENV
JRUBY_OPTS
JAVA_HOME

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
