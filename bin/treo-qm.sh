#!/bin/bash

# prepare PHP
php=$2

# prepare file(s) path
killer="data/kill-treo-qm.txt"

while true
do
    # kill process if it needs
   if [ -f $killer ]; then
     rm $killer;
     exit 1;
   fi

   if [ -f "data/qm-items.json" ]; then
     $php console.php qm --run > /dev/null 2>&1
   fi

   sleep 1;
done