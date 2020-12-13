# run .travis.yml - source binding for action.yml run script

export TRAVIS_YAML_ERROR_COUNT=0

#####
# get eventname
gh_eventname() {
  if [[ "schedule" = "$1" ]]; then
    echo "cron";
  else
    echo "$1";
  fi
}

#####
# get reference name (any type)
gh_refname() {
  echo "${1#refs/*/}";
}

####
# get sudo true/false
gh_sudo() {
  if sudo -nlU "$(whoami)">/dev/null 2>&1; then
    printf 'true';
  else
    printf 'false';
  fi
}

#####
# input binding
gh_input() {
  export "$1"="$2"
}

#####
# reg -> gh_input
reg() {
  gh_input "$1" "${!1-$2}"
};


gh_state_export_count=0
gh_state_export_stage=0

#####
# travis environment export
gh_export() {
  if (( gh_state_export_count == 0 )); then
    if (( gh_state_export_stage == 0 )); then
      printf '::group::env: \e[34m%s\e[0m\n' "${TRAVIS_YAML_FILE:-.travis.yml}"
    else
      printf '::group::env: \e[34m%s\e[0m (post)\n' "${TRAVIS_YAML_FILE:-.travis.yml}"
    fi
    (( ++gh_state_export_stage ))
  fi
  (( ++gh_state_export_count ))

  if [[ -z ${!1+x} ]]; then
    printf '  \e[90m%s\e[0m\n' "$1"
  else
    printf '  %s: %s\n' "$1" "${!1}"
  fi

  # shellcheck disable=SC2163
  export "${1:?}";

  if (( gh_state_export_stage == 2 )); then
    printf '%s=%s\n' "$1" "${!1}" >> "$GITHUB_ENV";
  fi
}

#####
# define environment variable with defaults and input binding
# 1: variable name
# 2: default value
# 3: input binding
gh_var() {
  local val
  val=${!1:-"${2:-}"}
  if [[ -n "${3:-}" ]]; then
    val="${!3:-"$val"}"
  fi
  eval "$1"='$val'
  # shellcheck disable=SC2163
  export -- "$1"
}

####
# compose environment table from environment variables
gh_env() {
  for i in $1; do
    gh_export "$i" ""
  done
}

#####
# close export channel
gh_close_export() {
  printf '::endgroup::\n'
  export gh_state_export_count=0
}

####
# format true / false from mixed leaning towards "$2-false" if
# neither true or false
gh_fmt_bool_def() {
  if [[ "$1" == "true" ]]; then
    printf 'true';
  elif [[ "$1" == "false" ]]; then
    printf 'false';
  else
    printf '%s' "${2-false}";
  fi
}

####
# format success / failure from build result status
gh_fmt_build_result() {
  if [[ $1 -eq 0 ]]; then printf 'success'; else printf 'failure'; fi
}

#####
# build build.sh file
gh_build_run() {
  gh_close_export
  # write build.sh
  "$GITHUB_ACTION_PATH"/lib/travis-script-builder.php \
      --file "$TRAVIS_YAML_FILE" "${travis_stages:-}" \
      > "$GITHUB_ACTION_PATH"/build.sh
  # execute build.sh (error fence)
  set +e
    /bin/bash "$GITHUB_ACTION_PATH"/build.sh
    export gh_build_result=$?
  set -e
  export TRAVIS_TEST_RESULT=$gh_build_result
  # action output
  printf '::set-output name=%s::%s\n' "exit-status" "$gh_build_result"
  printf '::set-output name=%s::%s\n' "outcome" "$(gh_fmt_build_result "$gh_build_result")"
}

#####
# deal allow failure / TRAVIS_ALLOW_FAILURE
gh_allow_failure() {
  if [[ $gh_build_result -ne 0 ]] && [[ "$TRAVIS_ALLOW_FAILURE" = "true" ]]; then
    printf '\e[33mTRAVIS_ALLOW_FAILURE\e[34m for build exit status \e[0m%s\n' "$gh_build_result"
    export gh_build_result=0 # silent
  fi
  # action output
  printf '::set-output name=%s::%s\n' "conclusion" "$(gh_fmt_build_result "$gh_build_result")"
}

#####
# build terminator
gh_terminate() {
  gh_close_export
  exit $gh_build_result
}

####
# message first error and fold afterwards
gh_travis_result_error() {
  local result type
  result=$1
  TRAVIS_YAML_ERROR_COUNT=$((TRAVIS_YAML_ERROR_COUNT + 1))
  if [[ $TRAVIS_YAML_ERROR_COUNT -ne 1 ]]; then
    return
  fi

  type="error"
  if [[ "$TRAVIS_ALLOW_FAILURE" == "true" ]]; then
    type="warning"
  fi

  printf '::%s file=%s::.travis.yml: The command %q exited with %s.\n' \
      "$type" "${TRAVIS_YAML_FILE-.travis.yml}" "$TRAVIS_CMD" "$result"
  printf '::group::\e[34m%s\e[0m\n' "after error continuation"
}
export -f gh_travis_result_error
