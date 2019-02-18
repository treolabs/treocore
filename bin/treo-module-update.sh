#!/bin/bash

# prepare PHP
php=$2

# prepare file(s) path
path="data/treo-module-update.txt"
log="data/treo-module-update.log"

while true
do
   # is neet to update composer
   if [ -f $path ]; then
     # delete file
     rm $path;

     # prepare log
     echo "Updating dependencies" > $log 2>&1

     # composer update
     $php composer.phar run-script pre-update-cmd > /dev/null 2>&1
     if ! $php composer.phar update --no-dev --no-scripts >> $log 2>&1; then
       echo "{{error}}" >> $log 2>&1
     else
       $php composer.phar run-script post-update-cmd > /dev/null 2>&1
       echo "{{success}}" >> $log 2>&1
     fi

     # update stream log
     $php console.php composer-log > /dev/null 2>&1
   fi
   sleep 1;
done