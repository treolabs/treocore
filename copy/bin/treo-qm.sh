#!/bin/bash

# prepare stream
stream=$2

# prepare PHP
php=$3

while true
do
    # exit
   if [ -f "data/process-kill.txt" ]; then
     exit 1;
   fi

   if [ -f "data/qm-items-$stream.json" ]; then
     $php index.php qm $stream --run > /dev/null 2>&1
   fi

   sleep 1;
done