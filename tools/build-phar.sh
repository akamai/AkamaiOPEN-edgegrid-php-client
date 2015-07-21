#!/bin/sh
export PATH=vendor/bin:$PATH
if [[ -z $1 ]]
then
    export VERSION=""
else
	export VERSION="-$1"
fi

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $DIR && cd ../
if [[ ! -d "build/phar" ]]
then
    mkdir -p build/phar
fi

# Create the bootstrap file if necessary
echo "<?php
/* Generate the stub that will load the autoloader */
if (!file_exists(__DIR__ . '/../build/phar')) {
    mkdir(__DIR__ . '/../build/phar', 0775, true);
}

\$stub = <<<EOF
<?php
if (class_exists('Phar')) {
   Phar::mapPhar('akamai-open-edgegrid-client.phar');
}

Phar::interceptFileFuncs();
require_once 'phar://' .__FILE__. '/vendor/autoload.php';
__HALT_COMPILER(); ?>
EOF;

file_put_contents('build/phar/stub.php', \$stub);" > build/phar/bootstrap.php

php -dphar.readonly=0 ./vendor/bin/box build

mv akamai-open-edgegrid-client.phar "akamai-open-edgegrid-client${VERSION}.phar" 
