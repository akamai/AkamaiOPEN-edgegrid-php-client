#!/bin/sh
export PATH=vendor/bin:$PATH
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $DIR && cd ../
rm -Rf docs
composer install
./vendor/bin/apigen generate