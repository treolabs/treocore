#!/bin/bash

# prepare PHP
php=$2

while true
do
   # is neet to update composer
   if [ -f "data/composer-update.txt" ]; then
     # delete file
     rm "data/composer-update.txt";

     # run composer update command
     $php composer.phar update --no-dev > "data/composer.log" 2>&1
     echo "{{finished}}" >> "data/composer.log"

     # push to log
     $php console.php composer-log > /dev/null
   fi
   sleep 1;
done