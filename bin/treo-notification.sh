#!/bin/bash

# prepare PHP
php=$2

while true
do
   # exit
   if [ -f "data/process-kill.txt" ]; then
     exit 1;
   fi

   $php console.php notifications --refresh > /dev/null 2>&1

   sleep 5;
done