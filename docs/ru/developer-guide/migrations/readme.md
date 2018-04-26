# Migrations #
Время от времени, при обновлении модуля или системы, возникают ситуации когда одного лишь обновления схеми БД не достаточно и нужно выполнить ряд дополнительных действий и не только с БД. Для этих целей был разработан механизм Migrations.

Для того чтобы разработань "миграцию" для модуля нужно создать класс:
```
/application/Espo/Modules/{MODULE_NAME}/Migration/{VERSION}.php
```
Класс должен быть унаследован от `Espo\Modules\TreoCore\Core\Migration\AbstractMigration`

### Пример класса: ###
```
<?php

namespace Espo\Modules\TreoCore\Migration;

use Espo\Modules\TreoCore\Core\Migration\AbstractMigration;

/**
 * Version 1.9.0
 *
 * @author r.ratsun@zinitsolutions.com
 */
class V190 extends AbstractMigration
{
    /**
     * Up to current
     */
    public function up(): void
    {
        $this->runRebuild();
    }

    /**
     * Down to previous  version
     */
    public function down(): void
    {
        $this->runRebuild();
    }
}


```