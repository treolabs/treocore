<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Util;

/**
 * ProductTypePackage service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProductTypePackage extends Base
{

    /**
     * Get package product
     *
     * @param string $productId
     * @return array
     */
    public function getPackageProduct(string $productId): array
    {
        // prepare result
        $result = [
            'id'            => null,
            'priceUnitId'   => null,
            'priceUnitName' => null,
            'content'       => null,
            'basicUnit'     => null,
            'packingUnit'   => null,
        ];

        // get data from db
        $pdo  = $this->getEntityManager()->getPDO();
        $sql  = "SELECT
                  ptp.id                 AS id,
                  ptp.price_unit_id      AS priceUnitId,
                  pu.name                AS priceUnitName,
                  ptp.content            AS content,
                  ptp.basic_unit         AS basicUnit,
                  ptp.packing_unit       AS packingUnit
                FROM product_type_package AS ptp
                JOIN price_unit as pu ON pu.id = ptp.price_unit_id AND pu.deleted = 0
                WHERE 
                  ptp.deleted = 0
                 AND ptp.package_product_id =".$pdo->quote($productId);
        $sth  = $pdo->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($data[0])) ? $data[0] : $result;
    }

    /**
     * Update data
     *
     * @param string $id
     * @param array $data
     *
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        // prepare data
        $result  = false;
        $product = $this->getPackageProduct($id);

        if (is_null($product['id'])) {
            // prepare data
            $priceUnitId = $data['priceUnitId'];
            $content     = $data['content'];
            $basicUnit   = $data['basicUnit'];
            $packingUnit = $data['packingUnit'];

            // prepare sql
            $sql = "INSERT INTO product_type_package SET `id`='%s',`price_unit_id`='%s',`content`='%s'"
                .",`basic_unit`='%s',`packing_unit`='%s', `package_product_id`='%s'";
            $sql = sprintf($sql, Util::generateId(), $priceUnitId, $content, $basicUnit, $packingUnit, $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        } else {
            // prepare sql
            $sql = "UPDATE product_type_package SET `price_unit_id`='%s',`content`='%s',`basic_unit`='%s'"
                .",`packing_unit`='%s' WHERE package_product_id='%s'";
            $sql = sprintf($sql, $data['priceUnitId'], $data['content'], $data['basicUnit'], $data['packingUnit'], $id);

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

    /**
     * Delete by product id
     *
     * @param array $ids
     *
     * @return bool
     */
    public function deleteByProductId(array $ids): bool
    {
        // prepare data
        $result = false;

        if (!empty($ids)) {
            // prepare sql
            $sql = "DELETE FROM product_type_package WHERE package_product_id IN ('%s')";
            $sql = sprintf($sql, implode("','", $ids));

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
