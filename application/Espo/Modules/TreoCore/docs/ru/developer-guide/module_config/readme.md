# Module config #
Иногда в рамках модуля нужно добавить конфигурацию в общий конфиг. Для этих целей разработан механизм который позволяет это делать. 

### Для этого нужно создать следующий файл: ###
```
/application/Espo/Modules/{MODULE_NAME}/Configs/ModuleConfig.php
```

### Пример содержания конфига: ###
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Configs;

return [
    'productType' => [
        'simpleProduct' => [
            'name' => 'Simple Product'
        ],
        'bundleProduct' => [
            'name' => 'Bundle Product'
        ],
        'packageProduct' => [
            'name' => 'Package Product'
        ],
        'configurableProduct' => [
            'name' => 'Configurable Product'
        ]
    ]
];

```

### Как получить данные? ###
Как и указано выше конфигурация модуля будет записана в общий конфиг.
Для получения достаточно:
```
$this->getContainer()->get('config')->get('modules')['productType']
```
То есть с примера видно, что все конфиги модулей записываються в масив modules в виде ассоциативного масива.
