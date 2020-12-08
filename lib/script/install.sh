#!/bin/bash
# import from pipelines project
set -euo pipefail

SOURCE=../pipelines/src
TARGET=lib/ktomk/pipelines

cp_pl() {
  if [[ ! -d "$SOURCE" ]]; then return 0; fi # no pipelines no copy
  cp -r "$SOURCE"/"$1" "$TARGET"/"$1"
}

cp_pl Yaml
cp_pl Lib.php
cp_pl LibFs.php
cp_pl LibFsStream.php
cp_pl ErrorCatcher.php
