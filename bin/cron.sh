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

## call cron jobs
#$php index.php cron
#
## self upgrade process
#if [[ ! "$(ps ax | grep treo-self-upgrade.sh)" =~ "bin/treo-self-upgrade.sh $id" ]]; then
#    chmod +x bin/treo-self-upgrade.sh
#    setsid ./bin/treo-self-upgrade.sh $id $php >/dev/null 2>&1 &
#fi
#
## module update process
#if [[ ! "$(ps ax | grep treo-module-update.sh)" =~ "bin/treo-module-update.sh $id" ]]; then
#    chmod +x bin/treo-module-update.sh
#    setsid ./bin/treo-module-update.sh $id $php >/dev/null 2>&1 &
#fi
#
## queue manager process
#stream=0
#while [ $stream -lt 10 ]
#do
#  if [[ ! "$(ps ax | grep treo-qm.sh)" =~ "bin/treo-qm.sh $id $stream" ]]; then
#    setsid ./bin/treo-qm.sh $id $stream $php >/dev/null 2>&1 &
#  fi
#
#  (( stream++ ))
#done
#
## notification process
#if [[ ! "$(ps ax | grep treo-notification.sh)" =~ "bin/treo-notification.sh $id" ]]; then
#    chmod +x bin/treo-notification.sh
#    setsid ./bin/treo-notification.sh $id $php >/dev/null 2>&1 &
#fi