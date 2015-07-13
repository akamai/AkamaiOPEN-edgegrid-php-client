#!/bin/sh
cd ..
export PATH=vendor/bin:$PATH
php composer.phar install -q
phpunit
phploc --log-xml=./build/phploc.xml ./src
phpdox 
