#!/bin/bash
export PATH=vendor/bin:$PATH
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
if [[ -z $1 ]]
then
    printf "What version are you building? [dev]: "

    read VERSION
    if [[ -z $VERSION ]]
    then
        VERSION="dev"
    fi

    export VERSION="$VERSION"
else
	export VERSION="$1"
fi

if [[ $VERSION != "dev" ]]
then
    $DIR/check-version.sh $VERSION
    if [[ $? -ne 0 ]]
    then
        exit -1
    fi
fi

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $DIR && cd ../
if [[ ! -d "build/phar" ]]
then
    mkdir -p build/phar
fi

if [[ -f $HOME/.composer/vendor/bin/box ]]
then
    composer install --no-dev -o -q
    php -dphar.readonly=0 $HOME/.composer/vendor/bin/box compile
    composer install -q
else
    composer install -o -q
    php -dphar.readonly=0 ./vendor/bin/box compile
    composer install -q
fi


mv akamai-open-edgegrid-client.phar "akamai-open-edgegrid-client-${VERSION}.phar"

php "akamai-open-edgegrid-client-${VERSION}.phar"

echo "<?php
include 'akamai-open-edgegrid-client-${VERSION}.phar';
\$client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile('default', './tests/edgerc/.edgerc');
var_dump(\$client);" > test.php
echo "Running smoke test";
php test.php
rm test.php