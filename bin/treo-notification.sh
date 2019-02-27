#!/bin/bash

# prepare PHP
php=$2

# prepare file(s) path
killer="data/kill-treo-notification.txt"

while true
do
    # kill process if it needs
   if [ -f $killer ]; then
     rm $killer;
     exit 1;
   fi

   $php console.php notifications --refresh > /dev/null 2>&1

   sleep 5;
done