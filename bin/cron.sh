#!/bin/bash

# validation
if [ $# -ne 2 ]
  then
    echo "Wrong script arguments. Process ID and PHP is required";
    exit 1;
fi

# remove processes killer
if [ -f "data/process-kill.txt" ]; then
  rm "data/process-kill.txt";
fi

# prepare process id
id=$1

# prepare PHP
php=$2

$php bin/upgrade.php
rm "bin/upgrade.php";
php composer.phar update --no-dev