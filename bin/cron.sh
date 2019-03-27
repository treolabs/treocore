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

# set php
echo "$php" > "data/cli-php.txt"

# call cron jobs
$php console.php cron

# self upgrade process
if [[ ! "$(ps ax | grep treo-self-upgrade.sh)" =~ "bin/treo-self-upgrade.sh $id" ]]; then
    setsid ./bin/treo-self-upgrade.sh $id $php >/dev/null 2>&1 &
fi

# module update process
if [[ ! "$(ps ax | grep treo-module-update.sh)" =~ "bin/treo-module-update.sh $id" ]]; then
    setsid ./bin/treo-module-update.sh $id $php >/dev/null 2>&1 &
fi

# queue manager process
if [[ ! "$(ps ax | grep treo-qm.sh)" =~ "bin/treo-qm.sh $id" ]]; then
    setsid ./bin/treo-qm.sh $id $php >/dev/null 2>&1 &
fi

# notification process
if [[ ! "$(ps ax | grep treo-notification.sh)" =~ "bin/treo-notification.sh $id" ]]; then
    setsid ./bin/treo-notification.sh $id $php >/dev/null 2>&1 &
fi