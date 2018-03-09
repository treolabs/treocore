# Cron Manager #
При помощи данного инструмента предоставляется возможность создавать Cron Jobs.
В рамках TreoCRM мы расширили базовую реализацию CronManager и теперь есть возможность создавать Jobs без предварительных записей в БД.

### Способы создания Jobs без предварительных записей в БД: ###
  * При помощи конфига
  * При помощи сервиса который являеться имплементом от \Espo\Modules\TreoCrm\Services\InterfaceJobCreatorService

### Как использовать? Примеры: ###
Для того чтобы создать Jobs без предварительных записей в БД в модуле нужно создать конфиг файл
```
application/Espo/Modules/{MODULE_NAME}/Configs/Jobs.php 
```
Содержимое конфига должно быть следующим:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\Import\Configs;

return [
    'scheduledJobs'         => [
        [
            'scheduling' => '*/5 * * * *',
            'service'    => 'ImportJob',
            'method'     => 'import',
            'name'       => 'ImportJob',
            'data'       => []
        ],
        [
            'scheduling' => '0 * * * *',
            'service'    => 'ImportJob',
            'method'     => 'clearHistory',
            'name'       => 'ImportJob',
            'data'       => []
        ],
        [
            'scheduling' => '*/5 * * * *',
            'service'    => 'ImportJob',
            'method'     => 'importRestore',
            'name'       => 'ImportJob',
            'data'       => []
        ]
    ],
    'scheduledJobsServices' => [
        'ImportCronJob'
    ]
];
```
**scheduledJobs** - здесь мы в виде массива описываем ScheduledJobs
  * scheduling - cron настройка
  * service - имя сервиса
  * method - имя метода в сервисе. На данный метод будет приходит массив данных указанный в data
  * name - имя джоба в системе
  * data - массив данных который будет отправляться на сервис

**scheduledJobsServices** - здесь мы в виде массива указываем сервисы которые являеться имплементом от \Espo\Modules\TreoCrm\Services\InterfaceJobCreatorService. В рамках сервиса вызывается метод который формирует такие же джобы как и в scheduledJobs.