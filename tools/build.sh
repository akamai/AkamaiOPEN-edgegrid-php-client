#!/bin/bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

echo "Checking installer"
if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo "ERROR: Invalid installer checksum"
    echo "Installer corrupt"
    php -r "unlink('composer-setup.php');"
    exit 1
fi
echo "Installer verified"

php composer-setup.php
php -r "unlink('composer-setup.php');"
mkdir -p bin
mv composer.phar bin/composer
export PATH=$(pwd)/bin:$PATH
composer update
XDEBUG_MODE=coverage ./vendor/bin/phpunit --log-junit test/tests-php.xml --coverage-cobertura test/coverage/cobertura-coverage.xml
xsltproc tools/phpunit-4.0-to-junit.xsl test/tests-php.xml > test/tests-junit.xml
./tools/build-phar.sh