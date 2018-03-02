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

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;

/**
 * Supplier controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Supplier extends AbstractController
{
    /**
     * Get Product action
     *
     * @ApiDescription(description="Get Product in Supplier")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Supplier/{supplier_id}/product")
     * @ApiParams(name="supplier_id", type="string", is_required=1, description="Supplier id")
     * @ApiReturn(sample="[{
     *     'supplierProductId': 'string',
     *     'productId': 'bool',
     *     'productName': 'string',
     *     'productSku': 'string',
     *     'isActive': 'bool'
     * },{}]")
     *
     * @param string $supplierId
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function getProduct(string $supplierId): array
    {
        if ($this->isReadEntity($this->name, $supplierId)) {
            return $this->getRecordService()->getProduct($supplierId);
        }

        throw new Exceptions\Error();
    }
}
