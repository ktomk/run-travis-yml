#!/bin/bash
#!!DEL!! source /etc/profile

ANSI_RED="\033[31;1m"
ANSI_GREEN="\033[32;1m"
ANSI_YELLOW="\033[33;1m"
ANSI_RESET="\033[0m"
ANSI_CLEAR="\033[0K"

TRAVIS_TEST_RESULT=
TRAVIS_CMD=

TRAVIS_RNYML_ERROR_COUNT=0

function travis_cmd() {
  local assert output outnp2 noexec display retry timing cmd result

  cmd=$1
  TRAVIS_CMD=$cmd
  shift

  while true; do
    case "$1" in
      --assert)  assert=true; shift ;;
      --echo)    output=true; shift ;;
      --echonp2) outnp2=true; shift ;;
      --noexec)  noexec=true; shift ;;
      --display) display=$2;  shift 2;;
      --retry)   retry=true;  shift ;;
      --timing)  timing=true; shift ;;
      *) break ;;
    esac
  done

  if [[ -n "$timing" ]]; then
    travis_time_start
  fi

  if [[ -n "$outnp2" ]]; then # github-action collapse w/ folder name
    printf "$ %s" "${display:-$cmd}" | tail -n +2
  elif [[ -n "$output" ]]; then
    printf '$ %s\n' "${display:-$cmd}"
  fi

  if [[ -z "$noexec" ]]; then
    if [[ -n "$retry" ]]; then
      travis_retry eval "$cmd"
    else
      eval "$cmd"
    fi
    result=$?
  else
    echo "dry-run, skipping command execution with exit status 0"
    result=0
  fi

  if [[ -n "$timing" ]]; then
    travis_time_finish
  fi

  if [[ -n "$assert" ]]; then
    travis_assert $result
  fi

  return $result
}

travis_time_start() {
  travis_timer_id=$(printf %08x $(( RANDOM * RANDOM )))
  travis_start_time=$(travis_nanoseconds)
  #!!DEL!! echo -en "travis_time:start:$travis_timer_id\r${ANSI_CLEAR}"
}

####
# message timing
travis_rnyml_gh_timings() {
  local dns="${1-0}"
  local pms ps total_duration tpms tps
  pms=$((dns/1000000))
  ps=$((pms/1000))
  pms=$((pms-ps*1000))

  total_duration=$((travis_end_time - TRAVIS_RNYML_START_TIME))
  tpms=$((total_duration/1000000))
  tps=$((tpms/1000))
  tpms=$((tpms-tps*1000))

  printf '\e[90m[info]\e[0m \e[34m%d.%03d\e[0m s / \e[34m%d.%03d\e[0m total\n' $((ps)) $((pms)) $((tps)) $((tpms))
}

travis_time_finish() {
  local result=$?
  travis_end_time=$(travis_nanoseconds)
  local duration=$(($travis_end_time-$travis_start_time))
  #!!DEL!! echo -en "travis_time:end:$travis_timer_id:start=$travis_start_time,finish=$travis_end_time,duration=$duration\r${ANSI_CLEAR}"
  travis_rnyml_gh_timings "$duration"
  return $result
}

function travis_nanoseconds() {
  local cmd="date"
  local format="+%s%N"
  local os=$(uname)

  if hash gdate > /dev/null 2>&1; then
    cmd="gdate" # use gdate if available
  elif [[ "$os" = Darwin ]]; then
    format="+%s000000000" # fallback to second precision on darwin (does not support %N)
  fi

  $cmd -u $format
}

travis_assert() {
  local result=${1:-$?}
  if [ $result -ne 0 ]; then
    echo -e "\n${ANSI_RED}The command \"$TRAVIS_CMD\" failed and exited with $result during $TRAVIS_STAGE.${ANSI_RESET}\n\nYour build has been stopped."
    travis_terminate 2
  fi
}

####
# message first error and fold afterwards
travis_rnyml_gh_travis_result_error() {
  local result type
  result=$1
  TRAVIS_RNYML_ERROR_COUNT=$((TRAVIS_RNYML_ERROR_COUNT + 1))
  if [[ $TRAVIS_RNYML_ERROR_COUNT -ne 1 ]]; then
    return
  fi

  type="error"
  if [[ "$TRAVIS_ALLOW_FAILURE" == "true" ]]; then
    type="warning"
  fi

  printf '::%s file=%s::.travis.yml: The command %q exited with %s.\n' \
      "$type" "$TRAVIS_YAML_FILE" "$TRAVIS_CMD" "$result"
  printf '::group::\e[34m%s\e[0m\n' "after error continuation"
}

travis_result() {
  local result=$1
  export TRAVIS_TEST_RESULT=$(( ${TRAVIS_TEST_RESULT:-0} | $(($result != 0)) ))

  if [ $result -eq 0 ]; then
    echo -e "\n${ANSI_GREEN}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
  else
    echo -e "\n${ANSI_RED}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
    travis_rnyml_gh_travis_result_error "$result"
  fi
}

travis_terminate() {
  pkill -9 -P $$ &> /dev/null || true
  exit $1
}

travis_wait() {
  local timeout=$1

  if [[ $timeout =~ ^[0-9]+$ ]]; then
    # looks like an integer, so we assume it's a timeout
    shift
  else
    # default value
    timeout=20
  fi

  local cmd="$@"
  local log_file=travis_wait_$$.log

  $cmd &>$log_file &
  local cmd_pid=$!

  travis_jigger $! $timeout $cmd &
  local jigger_pid=$!
  local result

  {
    wait $cmd_pid 2>/dev/null
    result=$?
    ps -p$jigger_pid &>/dev/null && kill $jigger_pid
  } || return 1

  if [ $result -eq 0 ]; then
    echo -e "\n${ANSI_GREEN}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
  else
    echo -e "\n${ANSI_RED}The command \"$TRAVIS_CMD\" exited with $result.${ANSI_RESET}"
  fi

  echo -e "\n${ANSI_GREEN}Log:${ANSI_RESET}\n"
  cat $log_file

  return $result
}

travis_jigger() {
  # helper method for travis_wait()
  local cmd_pid=$1
  shift
  local timeout=$1 # in minutes
  shift
  local count=0

  # clear the line
  echo -e "\n"

  while [ $count -lt $timeout ]; do
    count=$(($count + 1))
    echo -ne "Still running ($count of $timeout): $@\r"
    sleep 60
  done

  echo -e "\n${ANSI_RED}Timeout (${timeout} minutes) reached. Terminating \"$@\"${ANSI_RESET}\n"
  kill -9 $cmd_pid
}

travis_retry() {
  local result=0
  local count=1
  while [ $count -le 3 ]; do
    [ $result -ne 0 ] && {
      printf '\n::endgroup::\n'
      echo -e "${ANSI_RED}The command \"$@\" failed. Retrying, $count of 3.${ANSI_RESET}\n" >&2
    }
    "$@"
    result=$?
    [ $result -eq 0 ] && break
    count=$(($count + 1))
    sleep 1
  done

  [ $count -gt 3 ] && {
    echo -e "\n${ANSI_RED}The command \"$@\" failed 3 times.${ANSI_RESET}\n" >&2
  }

  return $result
}

travis_fold() {
  local action=$1
  local name=$2
  #!!DEL!! echo -en "travis_fold:${action}:${name}\r${ANSI_CLEAR}"
  if [[ $action = "start" ]]; then
    printf '::group::%s\n' "$name"
  else
    printf '::endgroup::\n'
  fi
}

decrypt() {
  echo $1 | base64 -d | openssl rsautl -decrypt -inkey ~/.ssh/id_rsa.repo
}

TRAVIS_RNYML_START_TIME=$(travis_nanoseconds)

#!!DEL!! mkdir -p <%= BUILD_DIR %>
#!!DEL!! cd       <%= BUILD_DIR %>
