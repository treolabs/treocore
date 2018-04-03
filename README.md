## What is TreoPIM
TreoPIM is a single page application (SPA) with an API-centric architecture and flexible data modell based on entities, entity attrubitues and relations of all kinds among them. TreoPIM allows you to gather and store all your product content in one place, enrich it and spread it to several channels like own onlineshop, amazon, ebay, onlineshops of your disctributors, on a tablet or mobile application. TreoPIM will help your to structure and organize all you flexible data and get rid of excell mess.

### Requirements

* PHP 7.1 or above (with json, openssl, pdo_mysql, zip, gd, mbstring, imap, curl, xml extensions);
* MySQL 5.5.3 or above.

### Documentation

Documentation for administrators, users and developers is available [here](docs/).

### Installation
####Installation by composer
1. Install [composer](https://getcomposer.org/doc/00-intro.md).
2. If composer is installed globally run 
   ```
   composer create-project treo/treo treopim
   ```
   or if locally run
   ```
   php composer.phar create-project treo/treo treopim
   ```
3. Install TreoPIM by following the TreoPIM installation wizard in web interface.

####Manual installation 
1. Download or clone treopim:
    ````
    git clone https://github.com/ZinitSolutionsGmbH/treopim.git
    ````
2.  cd {treopim_path} example:
    ```
    cd treopim
    ```
3. Create project:
    ```
    php composer.phar create-project
    ```
4. Install TreoPIM by following the TreoPIM installation wizard in web interface.

### License

TreoPIM is published under the GNU GPLv3 [license](LICENSE.txt).

### About Us

TreoPIM is a developed and supported by [Zinit Solutions GmbH](https://zinitsolutions.de/).

