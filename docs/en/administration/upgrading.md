## Upgrading

####Upgrade from CLI
Command to run system updating:
```
php console.php upgrade <versionTo> <action>
```
, where is:
* ``<versionTo>`` – version to which the system will be updated. If needed version don't exist you get a corresponding message;
* ``<action>`` – there are available two values:
    - ```--download``` – automatically download required upgrade packages based on your current version, which be stored in `data/upload/upgrades` folder;
    - ```--force``` – will be run upgrade of your system after downloading upgrade packages.

####Upgrade from UI
1. Check your current version

    Go to the Administration > Upgrade page.

    ![upgrade_page](../../../docs/_assets/upgrading/upgrade_page_en.png)

    There you can get current version of your system, list of available versions to upgrade and detail info about it.

2. Run upgrade

    After selecting the desired version you can run upgrade by clicking on the corresponding button.
    
    Then you will be able to view detailed upgrade logs.
    
    