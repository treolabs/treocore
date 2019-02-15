#!/bin/bash

# validation
if [ $# -ne 2 ]
  then
    echo "Wrong script arguments. Process ID and PHP is required";
    exit 1;
fi

# prepare process id
id=$1

# prepare PHP
php=$2

# call cron jobs
$php console.php cron

# call treo-composer
if [[ ! "$(ps ax | grep treo-upgrade.sh)" =~ "bin/treo-upgrade.sh $id" ]]; then
    bash ./bin/treo-upgrade.sh $id $php
fi

# call treo-composer
if [[ ! "$(ps ax | grep treo-composer.sh)" =~ "bin/treo-composer.sh $id" ]]; then
    bash ./bin/treo-composer.sh $id $php
fi