#!/bin/bash

# prepare PHP
php=$1

# prepare path
isUpdate="data/composer-update.txt";

while true
do
   # is neet to update composer
   if [ -f $isUpdate ]; then
     # delete file
     rm $isUpdate;

     # run composer update command
     $php composer.phar update --no-dev > "data/composer.log" 2>&1
     echo "{{finished}}" >> "data/composer.log"

     # push to log
     $php console.php composer-log > /dev/null
   fi
   sleep 1;
done