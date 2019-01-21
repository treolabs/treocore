#!/bin/bash

# validation
if [ $# -ne 1 ]
  then
    echo "Wrong script arguments. Only PHP required";
    exit 1;
fi

# prepare PHP
php=$1

# call cron jobs
$php console.php cron

# call treo-composer
if [[ ! "$(ps ax | grep treo-composer.sh)" =~ "bin/treo-composer.sh" ]]; then
    bash ./bin/treo-composer.sh $php
fi