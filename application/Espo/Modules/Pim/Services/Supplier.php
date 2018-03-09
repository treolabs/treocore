<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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
