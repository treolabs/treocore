#!/bin/bash

# start PHP CodeSniffer
phpcs="$(/usr/bin/php tools/phpcs.phar --standard=PSR2 application/Treo/)"
if [ ! -z "$phpcs" ]
then
  echo "PHP CodeSniffer failed! Call 'php tools/phpcs.phar --standard=PSR2 application/Treo/' for details."
  exit 1
fi

# start PHPUnit
/usr/bin/php tools/phpunit.phar --bootstrap bootstrap.php tests