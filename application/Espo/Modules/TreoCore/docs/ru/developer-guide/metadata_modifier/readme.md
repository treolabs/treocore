# Metadata modifier #
Иногда в рамках модуля нужно изменить метаданные. Для этих целей разработан механизм который позволяет это делать. Для этого нужно создать следующий файл:
```
/application/Espo/Modules/{MODULE_NAME}/Metadata/Metadata.php
```
Класс должен быть унаследован от `Espo\Modules\TreoCore\Metadata\AbstractMetadata`

### Пример класса: ###
```
<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCore\Metadata;

/**
 * Metadata
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Metadata extends AbstractMetadata
{
    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    public function modify(array $data): array
    {
       return $data;
    }
}

```
На вход метода **modify** передаються метаданные и ожидаеться, что метод отдаст их же и обратно, но теперь мы можем изменять их по своему усмотрению. В классе доступный Container.