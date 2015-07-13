#!/bin/sh
export PATH=vendor/bin:$PATH
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
cd $DIR && cd ../
RESULT=$(phpcs --colors --standard=PSR1,PSR2 ./src/)
echo "$RESULT"
echo $RESULT | grep "PHPCBF CAN FIX" > /dev/null
if [[ $? -eq 0 ]]
then
    printf "Would you like to fix errors? [Y/n] "
    read answer
    if [[ $answer != "n" ]]
    then
        echo "Running phpcbf: "
        phpcbf --standard=PSR1,PSR2 ./src/
        echo "Running php-cs-fixer: "
        php-cs-fixer fix ./src --level=psr2
    fi
fi
