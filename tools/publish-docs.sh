#!/bin/bash

if [ $# -ne 1 ]; then
    echo "usage: ./publish-docs.sh \"commit message\""
    exit 1;
fi

mv docs docs-temp
git stash
git checkout gh-pages
sleep 3

cp -R docs-temp/* .

git add *
git commit -m "$1"
git push origin gh-pages

git checkout master
rm -Rf docs
mv docs-temp docs
git stash apply