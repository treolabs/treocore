<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Layouts;

use Espo\Modules\TreoCrm\Layouts\AbstractLayout;

/**
 * Product layout
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Product extends AbstractLayout
{

    /**
     * Layout list
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutList(array $data): array
    {
        // image can not be sortable
        foreach ($data as $k => $v) {
            if ($v['name'] == 'image') {
                $data[$k] = array_merge($v, ['notSortable' => true]);
            }
        }

        return $data;
    }
}
