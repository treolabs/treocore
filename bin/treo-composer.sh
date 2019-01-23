#!/bin/bash

# prepare PHP
php=$1

log="data/composer.log"

while true
do
   # is neet to validate composer
   if [ -f "data/composer-validate.txt" ]; then
     # delete file
     rm "data/composer-validate.txt";

     # run composer validate command
     $php composer.phar update --no-dev --dry-run -d=data/composer-validate > "$log" 2>&1
     echo "{{finished}}" >> "$log"
   fi

   # is neet to update composer
   if [ -f "data/composer-update.txt" ]; then
     # delete file
     rm "data/composer-update.txt";

     # run composer update command
     $php composer.phar update --no-dev > "$log" 2>&1
     echo "{{finished}}" >> "$log"

     # push to log
     $php console.php composer-log > /dev/null
   fi
   sleep 1;
done