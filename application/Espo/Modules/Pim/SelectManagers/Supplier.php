<?php

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Supplier
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Supplier extends AbstractSelectManager
{

    /**
     * NotLinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(&$result)
    {
        // prepare data
        $productId = (string)$this->getSelectCondition('notLinkedWithProduct');

        foreach ($this->getProductSuppliers($productId) as $row) {
            $result['whereClause'][] = [
                'id!=' => $row['supplier_id']
            ];
        }
    }

    /**
     * Get product suppliers
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductSuppliers($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          supplier_id
        FROM
          supplier_product
        WHERE
          	product_id =' . $pdo->quote($productId) . '
          AND deleted = 0';
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
