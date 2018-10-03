# Layout modifier #
В связи с тем, что модули могут расширять один другого есть необходимость изменять лояуты при активации модуля. 
Для этого предусмотрено несколько способов:
  *  Внести изменения в рамках модуля (работает по принципу array_merge_recursive)
  *  Внести изменения в каталоге **Custom** (имеет приоритет выше чем изменения в рамках модуля)
  *  Использовать механизм финальной модификации лояута. Механизм финальной модификации приминяется последним
 
### Как использовать механизм финальной модификации лояута? ###
В рамках любого модуля нужно создать класс который будет унаследован от `Treo\Layouts\AbstractLayout`.
Класс нужно разместить в 
```
/application/Espo/Modules/{MODULE_NAME}/Layouts/{ENTITY_NAME}.php
```
Механизм будет искать файлы в активных модулях и по имени класса будет понимать лояут какой сущности нужно модифицировать.
Для того чтобы механизм понимал какой именно лояут нужно модифицировать, нужно правильно указать имя метода. На входи метода приходит массив данных, на выход ожидается тот же массив. В классе есть **Container**.
#### Правило формирования имени метода: ####
```
'layout'.ucfirst($layoutName)
```

### Пример: ###
`/application/Espo/Modules/Pricing/Layouts/Product.php`
```
 <?php
declare(strict_types = 1);

namespace Espo\Modules\Pricing\Layouts;

use Treo\Layouts\AbstractLayout;

/**
 * Product layout
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Product extends AbstractLayout
{

    /**
     * Layout detail
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutDetail(array $data): array
    {
        foreach ($data as $panelKey => $panel) {
            foreach ($panel['rows'] as $blockKey => $block) {
                foreach ($block as $k => $row) {
                    // turn off final price field
                    if ($row['name'] == 'finalPrice') {
                        $data[$panelKey]['rows'][$blockKey][$k] = false;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Layout detail small
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutDetailSmall(array $data): array
    {
        foreach ($data as $panelKey => $panel) {
            foreach ($panel['rows'] as $blockKey => $block) {
                foreach ($block as $k => $row) {
                    // turn off final price field
                    if ($row['name'] == 'finalPrice') {
                        $data[$panelKey]['rows'][$blockKey][$k] = false;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Layout list
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutList(array $data): array
    {
        foreach ($data as $k => $row) {
            // turn off final price field
            if ($row['name'] == 'finalPrice') {
                unset($data[$k]);
            }
        }

        return $data;
    }
}

``` 