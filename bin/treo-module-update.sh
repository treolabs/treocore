#!/bin/bash

# prepare PHP
php=$2

# prepare file(s) path
path="data/treo-module-update.txt"
log="data/treo-module-update.log"
killer="data/kill-treo-module-update.txt"

while true
do
   # kill process if it needs
   if [ -f $killer ]; then
     rm $killer;
     exit 1;
   fi

   if [ -f $path ]; then
     # delete file
     rm $path;

     # start
     echo -e "Modules updating has been started:\n" > $log 2>&1

     # composer update
     $php composer.phar run-script pre-update-cmd > /dev/null 2>&1
     if ! $php composer.phar update --no-dev --no-scripts >> $log 2>&1; then
       echo "{{error}}" >> $log 2>&1
     else
       $php composer.phar run-script post-update-cmd > /dev/null 2>&1
       echo "{{success}}" >> $log 2>&1
     fi

     # push log to stream
     $php console.php composer --push-log module-update > /dev/null 2>&1
   fi

   sleep 1;
done