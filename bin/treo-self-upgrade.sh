#!/bin/bash

# prepare PHP
php=$2

# prepare file(s) path
path="data/treo-self-upgrade.txt"
log="data/treo-self-upgrade.log"

while true
do
   # is neet to update composer
   if [ -f $path ]; then
     # get version from
     from=$(sed -n '1p' $path)

     #get version to
     to=$(sed -n '2p' $path)

     # delete file
     rm $path;

     # download package
     echo "1. Downloading upgrade package" > $log 2>&1
     if ! $php console.php upgrade $to --download > /dev/null 2>&1; then
       echo "ERROR" >> $log 2>&1
       echo "{{error}}" >> $log 2>&1
     else
       echo -e "OK\n" >> $log 2>&1

       # composer update
       echo "2. Updating dependencies" >> $log 2>&1
       $php console.php composer --upgrade-core "${from}_${to}" > /dev/null 2>&1
       $php composer.phar run-script pre-update-cmd > /dev/null 2>&1
       if ! $php composer.phar update --no-dev --no-scripts >> $log 2>&1; then
         $php console.php composer --upgrade-core none > /dev/null 2>&1
         echo "{{error}}" >> $log 2>&1
       else
         echo -e "OK\n" >> $log 2>&1

         # upgrade
         echo "3. Upgrading core" >> $log 2>&1
         $php console.php upgrade $to --force > /dev/null 2>&1
         $php console.php migrate TreoCore $from $to > /dev/null 2>&1
         $php composer.phar run-script post-update-cmd > /dev/null 2>&1
         echo -e "OK\n" >> $log 2>&1
         echo "{{success}}" >> $log 2>&1
       fi
     fi
   fi
   sleep 1;
done