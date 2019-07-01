#!/bin/bash

# prepare PHP
php=$2

while true
do
   # exit
   if [ -f "data/process-kill.txt" ]; then
     exit 1;
   fi

   $php index.php notifications --refresh > /dev/null 2>&1

   sleep 5;
done