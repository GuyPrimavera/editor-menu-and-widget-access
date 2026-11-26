#!/usr/bin/env bash
# Use only the prepared disposable browser test installation.
set -euo pipefail
if [[ ${EMWA_TEST_SITE:-} != 1 || -z ${1:-} ]]; then
    echo 'Use EMWA_TEST_SITE=1 and pass a disposable WordPress path.' >&2
    exit 1
fi
setsid env PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8999 -t "$1" >/tmp/emwa-menu-php.log 2>&1 &
server=$!
trap 'kill -- -"$server" 2>/dev/null || kill "$server" 2>/dev/null || true' EXIT
EMWA_TEST_PATH="$1" node "$(dirname "$0")/localisation.cjs"
