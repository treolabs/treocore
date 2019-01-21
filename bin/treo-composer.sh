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

     if [ $? -eq 0 ]
     then
       # run rebuild
       $php console.php rebuild > /dev/null 2>&1
     fi
   fi
   sleep 1;
done