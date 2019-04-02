### Requirements

* Unix-based system
* PHP 7.1 or above (with pdo_mysql, openssl, json, zip, gd, mbstring, xml, curl,exif extensions)
* MySQL 5.5.3 or above

See [Server Configuration](docs/en/administration/server-configuration.md) article for more information.

### Installation
To create your new TreoCore application, first make sure you're using PHP 7.1 or above and have [Composer](https://getcomposer.org/) installed. 

1. Create your new project by running:
   ```
   composer create-project treolabs/treocore my-treocore-project
   ```
2. Make cron handler files executable:
   ```
   chmod +x bin/cron.sh 
   ```
3. Configure crontab:
   ```
   * * * * * cd /var/www/my-treocore-project; ./bin/cron.sh process-treocore-1 /usr/bin/php 
   ```
   - **/var/www/my-treocore-project** - path to project root
   - **process-treocore-1** - an unique id of process. You should use different process id if you have few TreoPIM project in one server
   - **/usr/bin/php** - PHP7.1 or above
4. Install TreoCore by following installation wizard in web interface. Just go to http://YOUR_PROJECT/

### License

TreoCore is published under the GNU GPLv3 [license](LICENSE.txt).

### Support

TreoCore is a developed and supported by [TreoLabs GmbH](https://treolabs.com/).
