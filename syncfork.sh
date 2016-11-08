#!/bin/sh
fatal() {
  echo "$@" 2>&1
  exit 1
}

[ ! -d .git ] && fatal "No GIT repo!"
[ x"$(git rev-parse --abbrev-ref HEAD)" != x"upstream" ] \
  && fatal "You should be in the upstream branch"


if ! git remote -v | grep upstream ; then
  echo Configuring upstream
  git remote add upstream https://github.com/iclanzan/Backup.git
fi
git fetch upstream
git merge upstream/master
