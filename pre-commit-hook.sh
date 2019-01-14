#!/bin/sh

str="test";

if [ $str == "test" ]
then
    echo "Show message"
fi

# start PHP CodeSniffer
#/usr/bin/php tools/phpcs.phar --standard=PSR2 application/Treo/

# start PHPUnit
#/usr/bin/php tools/phpunit.phar --bootstrap bootstrap.php tests