<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

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
