## What is TreoCore?
TreoCore is an open-source software ecosystem developed by TreoLabs GmbH, made for rapid development of web-based responsive business applications of any kind (ERP, PIM, CRM, DMS, MDM, DAM etc.). TreoCore is distributed under GPLv3 License and is free. It has a lot of features right out-of-the box and thus is an excellent tool for cost-effective and timely application development.

TreoCore is a single page application (SPA) with an API-centric, service-oriented architecture and flexible data model based on configurable entities and relations. You can organize any data and related business processes directly in TreoCore, many of them by simple and user-friendly configuration.

### For whom?
TreoCore is the best fit **for businesses**, who want to:
* solve custom business problems
* store data and organize business processes
* set up and use a middleware to connect with third-party systems
* create added value and best experience for your employees, customers and partners
* extend the functionality of the existing software infrastructure

### Which Applications based on TreoCore are available?
* TreoPIM
* TreoCRM

Both applications can be used in a single instance and can be extended with numerous modules.

### What is on board?

| Feature                                     | Description                                                                                                                                             |
|---------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| Dashboards                                  | Use multiple dashboards to control all main activities in the system.                                                                                   |
| Module Manager                              | It allows to install or to update any module directly from the administration panel, just choose the version you want to use.                           |
| Entity Manager                              | You can configure the data model directly from the administration panel, create new or edit existing entities and set relations of different types.     |
| Dynamic field logic                         | You can configure the conditions that make some fields invisible, read-only or editable.                                                                |
| Layout manager                              | Use it to configure any User Interface in the system or to show up the panels for related entities, via drag-and-drop.                                  |
| Label manager                               | You can edit any label in the system, in all languages you want to use.                                                                                 |
| Configurable navigation                     | Use drag-and-drop functionality to set a navigation as you wish, also separately for each user, if needed.                                              |
| Scheduled Jobs                              | You can configure, which jobs should be run by cron and at what schedule.                                                                               |
| Notifications                               | Set up a system or e-mail notifications for different events in the system.                                                                             |
| Data import and Export                      | You can import or export any data to any and from any entity in the system, even those you have just created.                                           |
| Advanced mass updates                       | Choose the entries to be updated, set the new values and do a bulk update.                                                                              |
| Advanced search and filters                 | You can configure the filters and search criteria as you wish, and save them, if you want to use this saved filter later.                               |
| Portals                                     | Use this additional layer to give access to third parties to your system. Use portal roles to restrict their access.                                    |
| Change Log and Stream                       | See all changes to the entries (who, old and new value, when) and add your own notices with a timestamp and attachments.                                |
| Queue Manager                               | Use it if you want to run or control processes in the background.                                                                                       |
| Access Control Lists (ACL)                  | Enterprise Level ACL based on Teams and Roles, with access level (own, team, all). You are able to edit the permissions even for each field separately. |
| REST API                                    | Integrate it with any third-party software, fully automated.                                                                                            |

### What are the advantages of using it?
* Really quick time to market and low implementation costs!
* Configurable, flexible and customizable
* Free - 100% open source, licensed under GPLv3
* REST API
* Web-based and platform independent
* Based on modern technologies
* Good code quality
* Service-oriented architecture (SOA)
* Responsive and user-friendly UI
* Configurable (entities, relations, layouts, labels, navigation)
* Extensible with modules
* Very fast
* Easy to maintain and support
* Many out-of-the-box features
* Best for Rapid Application Development

### What technologies is it based on?
TreoCore was created based on EspoCRM. It uses:

* PHP7 - pure PHP, without any frameworks to achieve the best possible performance,
* backbone.js - framework for SPA Frontend,
* Composer - dependency manager for PHP,
* Some libraries from Zend Framework 3,
* MySQL 5.

### Integrations
TreoCore has a REST API and can be integrated with any third-party system. You can also use import and export functions or use our modules (import feeds and export feeds) to get even more flexibility.

### Documentation

- Documentation for administrators is available [here](docs/en/administration/).

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
   - **process-treocore-1** - an unique id of process. You should use different process id if you have few TreoCore project in one server
   - **/usr/bin/php** - PHP7.1 or above
4. Install TreoCore by following installation wizard in web interface. Just go to http://YOUR_PROJECT/

### License

TreoCore is published under the GNU GPLv3 [license](LICENSE.txt).

### Support

TreoCore is a developed and supported by [TreoLabs GmbH](https://treolabs.com/).
