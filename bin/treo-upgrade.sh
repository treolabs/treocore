#!/bin/bash

# prepare PHP
php=$2

# prepare log file
log="data/treo-upgrade.log"

version="3.3.10"

# start
echo -e "Core upgrading has been started\n" > $log 2>&1

# download package
echo "1. Downloading upgrade package" >> $log 2>&1
$php console.php upgrade $version --download >> $log 2>&1
if [[ "$(<$log)" =~ "Package downloading failed!" ]]; then
    echo "{{FAILED}}" >> $log 2>&1
    exit 1
fi
echo " " >> $log 2>&1

# composer update
echo "2. Updating dependencies" >> $log 2>&1
$php composer.phar run-script pre-update-cmd > /dev/null 2>&1
if ! $php composer.phar update --no-dev --no-scripts >> $log 2>&1; then
    echo "{{FAILED}}" >> $log 2>&1
    exit 1
fi
echo " " >> $log 2>&1