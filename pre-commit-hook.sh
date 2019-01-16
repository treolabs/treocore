#!/bin/bash
# For developers only!

# start PHP CodeSniffer
phpcs="$(vendor/bin/phpcs --standard=PSR2 application/Treo/)"
if [ ! -z "$phpcs" ]
then
  echo "PHP CodeSniffer failed! $phpcs"
  exit 1
fi

# start PHPUnit
vendor/bin/phpunit --bootstrap bootstrap.php tests