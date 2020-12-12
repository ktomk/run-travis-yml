# run .travis.yml

### travis environment variables

gh_var CI                                      true
gh_var HAS_JOSH_K_SEAL_OF_APPROVAL             true
gh_var HAS_ANTARES_THREE_LITTLE_FRONZIES_BADGE true
gh_var TRAVIS_ALLOW_FAILURE                    false allow_failure
# TRAVIS_APP_HOST - hostname of build-script compiling system - needs introspection on github runner
gh_var TRAVIS_BUILD_DIR                        "$GITHUB_WORKSPACE"
gh_var TRAVIS_BUILD_ID                         "$GITHUB_RUN_ID"
gh_var TRAVIS_BUILD_NUMBER                     "$GITHUB_RUN_NUMBER"
# TRAVIS_BRANCH:
#  * for push builds, or builds not triggered by a pull request, this is the name of the branch.
#             GITHUB_REF: The branch or tag ref that triggered the workflow. For example, refs/heads/feature-branch-1.
#                         If neither a branch or tag is available for the event type, the variable will not exist.
#             GITHUB_BASE_REF:
#                         Only set for forked repositories. The branch of the base repository.
#  * for builds triggered by a pull request this is the name of the branch targeted by the pull request.
#             ... test pull request ...
#  * for builds triggered by a tag, this is the same as the name of the tag (TRAVIS_TAG).
# Note: that for tags, git does not store the branch from which a commit was tagged.
gh_var TRAVIS_BRANCH                           "$(gh_refname "${GITHUB_BASE_REF:-"${GITHUB_REF:?"either GITHUB_BASE_REF or GITHUB_REF expected"}"}")"
# TRAVIS_BUILD_WEB_URL - URL build log
gh_var TRAVIS_COMMIT                           "$GITHUB_SHA"
# TRAVIS_COMMIT_MESSAGE
# TRAVIS_COMMIT_RANGE
gh_var TRAVIS_EVENT_TYPE                       "$(gh_eventname "${event_name?"event_name expected"}")"
# TRAVIS_JOB_ID
# TRAVIS_PULL_REQUEST:
#   * The pull request number if the current job is a pull request, “false” if it’s not a pull request
#   ... needs review ... test pull request ...
gh_var TRAVIS_PULL_REQUEST                     '' event_number
gh_var TRAVIS_REPO_SLUG                        '' repository
gh_var TRAVIS_YAML_FILE                        '.travis.yml' travis_file
gh_var TRAVIS_YAML_TEST_FILE                   "$TRAVIS_YAML_FILE"

### travis environment table
#
# travis default environment variables (long lists following)
#
# link: <https://docs.travis-ci.com/user/environment-variables/#default-environment-variables>
#
# excluded:
#   TRAVIS_TEST_RESULT      (internal)
#   TRAVIS_BUILD_STAGE_NAME (internal, todo)
gh_env \
"""
CI
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

TRAVIS_YAML_FILE

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
"""

### write/run .travis.yml, process results and finish

gh_build_run
gh_allow_failure

gh_export TRAVIS_ALLOW_FAILURE
gh_export TRAVIS_TEST_RESULT
gh_export TRAVIS_YAML_FILE

gh_terminate # done.
