#!/bin/bash
VERIFY_VERSION=`grep "const VERSION" ./src/Client.php | cut -d " " -f 8 | cut -d "'" -f 2`
if [[ $VERIFY_VERSION != $1 ]]
then
    (>&2 echo "Version \"$1\" does not match Client.php: \"$VERIFY_VERSION\"")
    exit -1
fi
exit 0