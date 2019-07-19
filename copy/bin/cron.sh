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

# call cron jobs
$php index.php cron

# composer process
if [[ ! "$(ps ax | grep treo-composer.sh)" =~ "bin/treo-composer.sh $id" ]]; then
    chmod +x bin/treo-composer.sh
    setsid ./bin/treo-composer.sh $id $php >/dev/null 2>&1 &
fi

# queue manager process
stream=0
while [ $stream -lt 2 ]
do
  if [[ ! "$(ps ax | grep treo-qm.sh)" =~ "bin/treo-qm.sh $id $stream" ]]; then
    setsid ./bin/treo-qm.sh $id $stream $php >/dev/null 2>&1 &
  fi

  (( stream++ ))
done

# notification process
if [[ ! "$(ps ax | grep treo-notification.sh)" =~ "bin/treo-notification.sh $id" ]]; then
    chmod +x bin/treo-notification.sh
    setsid ./bin/treo-notification.sh $id $php >/dev/null 2>&1 &
fi