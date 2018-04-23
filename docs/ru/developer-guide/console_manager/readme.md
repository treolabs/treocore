# Console Manager #
**Treo System** обладает системой для обработки CLI запросов.
При необходимости можно создать свой console route и разработать обработчик этого запроса.

### Чтобы создать свой console route нужно: ###
  * Настроить(создать) конфиг файл в модуле
  * Разработать класс обработчик запроса с консоли

### Как использовать? Пример: ###
Создаем файл
```
application/Espo/Modules/{MODULE_NAME}/Configs/Console.php 
```
Содержимое конфига должно быть следующим:
```
<?php
declare(strict_types=1);

namespace Espo\Modules\MyModule\Configs;

use Espo\Modules\MyModule\Console;

return [
    "clear cache"       => Console\ClearCache::class,
    "run task <taskId>" => Console\Task::class
];
```
Содержимое класса обработчика:
```
<?php
declare(strict_types=1);

namespace Espo\Modules\MyModule\Console;

/**
 * Task console
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Task extends AbstractConsole
{
    /**
     * Run action
     *
     * @param array $data
     */
    public function run(array $data): void
    {
        self::show('Task successfully finished', 1);
    }
}
```

В случае если будет вызвана команда ``` php console.php run task 123456```, то система вызвет обработчик ```Espo\Modules\MyModule\Console\Task``` и в качестве аргументом передаваемых на метод run, будет передан массив ```["taskId" => '123456']```.