<?php

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Attribute
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Attribute extends AbstractSelectManager
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

        foreach ($this->createService('Product')->getAttributes($productId) as $row) {
            $result['whereClause'][] = [
                'id!=' => $row['attributeId']
            ];
        }
    }

    /**
     * NotLinkedWithProductFamily filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProductFamily(&$result)
    {
        // prepare data
        $productFamilyId = (string)$this->getSelectCondition('notLinkedWithProductFamily');

        foreach ($this->getProductFamilyAttributes($productFamilyId) as $row) {
            $result['whereClause'][] = [
                'id!=' => $row['attribute_id']
            ];
        }
    }

    /**
     * Get product family attributes
     *
     * @param string $productFamilyId
     *
     * @return array
     */
    protected function getProductFamilyAttributes($productFamilyId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          attribute_id
        FROM
          product_family_attribute
        WHERE
          product_family_id =' . $pdo->quote($productFamilyId) . '
          AND deleted = 0';
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
