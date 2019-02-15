#!/bin/bash

# prepare PHP
php=$2

# prepare log file
log="data/treo-upgrade.log"

while true
do
   # is neet to update composer
   if [ -f "data/treo-upgrade.txt" ]; then
     # get version from
     from="3.3.8"

     #get version to
     to="3.3.10"

     # delete file
     rm "data/treo-upgrade.txt";

     # download package
     echo "1. Downloading upgrade package" > $log 2>&1
     if ! $php console.php upgrade $to --download > /dev/null 2>&1; then
       echo "ERROR" >> $log 2>&1
       echo "{{error}}" >> $log 2>&1
       exit 1
     fi
     echo -e "OK\n" >> $log 2>&1

     # composer update
     echo "2. Updating dependencies" >> $log 2>&1
     $php console.php composer-version $to --set > /dev/null 2>&1
     $php composer.phar run-script pre-update-cmd > /dev/null 2>&1
     if ! $php composer.phar update --no-dev --no-scripts >> $log 2>&1; then
       $php console.php composer-version $from --set > /dev/null 2>&1
       echo "{{error}}" >> $log 2>&1
       exit 1
     fi
     echo -e "OK\n" >> $log 2>&1

     # upgrade
     echo "3. Upgrading core" >> $log 2>&1
     $php console.php upgrade $to --force > /dev/null 2>&1
     $php console.php migrate TreoCore $from $to > /dev/null 2>&1
     $php composer.phar run-script post-update-cmd > /dev/null 2>&1
     echo -e "OK\n" >> $log 2>&1
     echo "{{success}}" >> $log 2>&1
   fi
   sleep 1;
done