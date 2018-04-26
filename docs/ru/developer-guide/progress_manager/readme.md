# Progress Manager #
При помощи данного инструмента предоставляется возможность создавать задачи которые будут выполняться долго, но при этом пользователю будет показан progress bar.

### Как создать progress job? ###
Для этого достаточно вызвать пушер
```
$this->getContainer()->get('progressManager')->push($name, $type, $data, $userId);
```
  * $name - Имя
  * $type - Тип джоба
  * $data - массив данных который будет отправляться на сервис (не обязательно)
  * $userId - Пользователь которому будут отбражатся уведомления Progress Manager (не обязательно)

### Что такое $type и как разработать новый? ###
Тип джоба это ключ по которому система понимает кто и как будет обрабатывать указанный джоб.

Для создания **нового типа** нужно:
#### 1. Объявить тип в конфиг файле `/application/Espo/Modules/{MODULE_NAME}/Configs/ProgressManager.php` ####
     Пример содержимого конфига:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\Export\Configs;

return [
    'actionService' => [
        'download_export_file' => 'DownloadAction'
    ],
    'type'          => [
        'export' => [
            'action'  => [
                'new'         => [
                ],
                'in_progress' => [
                ],
                'error'       => [
                ],
                'success'     => [
                    'download_export_file'
                ],
            ],
            'service' => 'ExportProgressManager'
        ]
    ]
];

```
* actionService - здесь указываем сервисы которые обрабатывает экшены
* type - указываем типы
  * action - здесь нужно указать экшены которые будут доступны при определенном статусе с фронта. С примера видно, что в рамках типа **export**, когда статус джоба будет **success**, будет доступна возможность скачать файл. С бекенда мы отдает только ключ и возможно данные, вся логика того как оно будет отображаться разрабатывается фронт разработчиком.
  * service - указывем сервис который буде обрабатывать тип
  
#### 2. Создать сервис обработчик джоба ####
Сервис должен быть имплементом от `Espo\Modules\TreoCore\Services\ProgressJobInterface`
Пример:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\Export\Services;

use Espo\Modules\TreoCore\Services\ProgressJobInterface;
use Espo\Modules\TreoCore\Services\AbstractProgressManager;

/**
 * ExportProgressManager service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ExportProgressManager extends AbstractProgressManager implements ProgressJobInterface
{

    /**
     * Execute progress job
     *
     * @param array $data
     *
     * @return bool
     */
    public function executeProgressJob(array $data): bool
    {
        return true;
    }

    /**
     * Get progress
     *
     * @return float
     */
    public function getProgress(): float
    {
        return 100;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return 'success';
    }
}

```
#### 3. Создать сервис экшенов которые используются (если используются) ####
Сервис должен быть имплементом от `Espo\Modules\TreoCore\Services\StatusActionInterface`
Пример:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Services\Base;

/**
 * CancelStatusAction service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CancelStatusAction extends Base implements StatusActionInterface
{

    /**
     * Get progress status action data
     *
     * @param array $data
     *
     * @return array
     */
    public function getProgressStatusActionData(array $data): array
    {
        return [];
    }

    /**
     * Cancel action
     *
     * @param string $id
     *
     * @return bool
     */
    public function cancel(string $id): bool
    {
        // prepare result
        $result = false;

        if (!empty($id)) {
            // prepare sql
            $sql = "UPDATE progress_manager SET `deleted`=1 WHERE id='%s'";
            $sql = sprintf($sql, $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
```

### Front-end часть ###
Данный инструмент предоставляет возможность добавлять действия, которые появляются во время и после завершения выполения каких либо операций в Progress Manager.
#### 1. Создание конфигурации ####
##### Response #####
В теле ответа приходит массив действий:
```
"actions": [
    {
        "type": "{ACTION}", 
        "data": {
            //some_data
        }
    }
]
```
Создаём конфигурационный файл `ProgressManager.json`, в котором добавляем необходимые действия. 
Путь к файлу: `/application/Espo/Modules/{MODULE_NAME}/Resources/metadata/clientDefs/ProgressManager.json`
Структура файла:
```
{
    "progressActionViews": {
        "{ACTION}": "{VIEW_PATH}"
    }
}
```
Где:
* {ACTION} - имя действия
* {VIEW_PATH} - путь к view, где реализовано действие
#### 2. Создаём view ####
Создаём view по пути {VIEW_PATH}.
Общая структура view:
```
Espo.define('{VIEW_PATH}', 'view', function (Dep) {
    return Dep.extend({
        //code
    });
});
```
#### Пример ####
##### Response #####
С сервера приходит массив действий:
```
"actions": [
    {
        "type": "showMessage", 
        "data": {
            "message": "Something wrong" 
        }
    }
] 
```
##### 1. Создаём конфигурацию #####
Создаём файл конфигурации, где указываем необходимое действие.
`/application/Espo/Modules/TreoCore/Resources/metadata/clientDefs/ProgressManager.json`
```
{
    "progressActionViews": {
        "showMessage": "treo-core:views/progress-manager/actions/show-message"
    }
}
```
##### 2. Создаём views: #####
По заданому пути создаём новый view, где реализуем необходимое действие.
`/client/modules/treo-core/src/views/progress-manager/actions/show-message.js`
Содержание:
```
Espo.define('treo-core:views/progress-manager/actions/show-message', 'view',
    Dep => Dep.extend({
        template: 'treo-core:progress-manager/actions/show-message',
        actionData: {},
        events: {
            'click [data-action="showMessageModal"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionShowMessageModal();
            },
        },
        setup() {
            Dep.prototype.setup.call(this);
            this.actionData = this.options.actionData || this.actionData;
        },
        data() {
            return {
                showButton: !!this.actionData.message
            };
        },
        actionShowMessageModal() {
            this.createView('modal', 'treo-core:views/progress-manager/modals/show-message', {
                message: this.actionData.message
            }, view => view.render());
        }
    })
);
```