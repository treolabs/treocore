<?php
declare(strict_types=1);


namespace Espo\Modules\Pim\Services;

use \Espo\Core\Templates\Services\Base;
use \PDO;

/**
 * Supplier service
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Supplier extends Base
{

    /**
     * Get Products for Supplier
     *
     * @param string $supplierId
     *
     * @return array
     */
    public function getProduct(string $supplierId): array
    {
        $productList = $this->getDBProduct($supplierId);
        // prepare data
        foreach ($productList as $key => $product) {
            $productList[$key]['isActive'] = (bool) $product['isActive'];
        }

        return $productList;
    }

    /**
     * Get Product for Supplier from DB
     *
     * @param string $supplierId
     *
     * @return array
     */
    protected function getDBProduct(string $supplierId): array
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  sp.id AS supplierProductId,
                  p.id AS productId,
                  p.name AS productName,
                  p.sku AS productSku,
                  p.is_active AS isActive
                FROM product AS p
                JOIN
                  supplier_product AS sp
                ON
                    p.id = sp.product_id
                WHERE
                  sp.deleted = 0
                  AND p.deleted = 0
                  AND sp.supplier_id = " . $pdo->quote($supplierId) . ";";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}
