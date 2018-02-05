# Progress Manager #
При помощи данного инструмента предоставляется возможность создавать задачи которые будут выполняться долго, но при этом пользователю будет показан progress bar.

### Как создать progress job? ###
Для этого достаточно вызвать пушер
```
$this->getContainer()->get('progressManager')->push($name, $type, $data);
```
  * $name - Имя
  * $type - Тип джоба
  * data - массив данных который будет отправляться на сервис (не обязательно)

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
Сервис должен быть имплементом от `Espo\Modules\TreoCrm\Services\ProgressJobInterface`
Пример:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\Export\Services;

use Espo\Modules\TreoCrm\Services\ProgressJobInterface;
use Espo\Modules\TreoCrm\Services\AbstractProgressManager;

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
Сервис должен быть имплементом от `Espo\Modules\TreoCrm\Services\StatusActionInterface`
Пример:
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

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