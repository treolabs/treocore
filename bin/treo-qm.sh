#!/bin/bash

# prepare PHP
php=$2

while true
do
   if [ -f "data/qm-items.json" ]; then
     $php console.php qm --run > /dev/null 2>&1
   fi
   sleep 1;
done