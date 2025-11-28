#!/bin/bash
set -eo pipefail

function dev-init {
	composer install && cd tools && composer install
}

function dev {
	yarn run dev
}

function help {
  printf "%s <task> [args]\n\nTasks:\n" "${0}"

  compgen -A function | grep -v "^_" | cat -n
}

TIMEFORMAT=$'\nTask completed in %3lR'
time "${@:-help}"