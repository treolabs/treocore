## What is TreoPIM
TreoPIM is a single page application (SPA) with an API-centric architecture and flexible data modell based on entities, entity attrubitues and relations of all kinds among them. TreoPIM allows you to gather and store all your product content in one place, enrich it and spread it to several channels like own onlineshop, amazon, ebay, onlineshops of your disctributors, on a tablet or mobile application. TreoPIM will help your to stracture and organize all you flexible data and get rid of excell mess.

### Requirements

* PHP 7.1 or above (with json, openssl, pdo_mysql, zip, gd, mbstring, imap, curl, xml extensions);
* MySQL 5.5.3 or above.

### Installation

1. Install [composer](https://getcomposer.org/doc/00-intro.md).
2. If composer is installed globally run 
   ```
   composer create-project symfony/website-skeleton my-project
   ```
   or if locally run
   ```
   php composer.phar create-project symfony/website-skeleton my-project
   ```

Never update composer dependencies if you are going to contribute code back.

To compose a proper config.php and populate database you can run install by opening `http(s)://{YOUR_CRM_URL}/install` location in a browser. Then open `data/config.php` file and add `isDeveloperMode => true`.

### License

TreoPIM is published under the GNU GPLv3 [license](https://raw.githubusercontent.com/espocrm/espocrm/master/LICENSE.txt).

