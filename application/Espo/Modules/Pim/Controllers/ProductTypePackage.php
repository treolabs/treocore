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
use Slim\Http\Request;

/**
 * ProductTypePackage controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProductTypePackage extends AbstractProductTypeController
{

    /**
     * @ApiDescription(description="Get package product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/view")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionView($params, $data, Request $request): array
    {
        if (!$this->getAcl()->check('Product', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('ProductTypePackage')->getPackageProduct($params['entity_id']);
    }

    /**
     * @ApiDescription(description="Update package product")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/update")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiParams(name="priceUnitId", type="string", is_required=1, description="Price Unit ID")
     * @ApiParams(name="content", type="string", is_required=1, description="Content")
     * @ApiParams(name="basicUnit", type="string", is_required=1, description="Basic Unit")
     * @ApiParams(name="packingUnit", type="string", is_required=0, description="Packing Unit")
     * @ApiReturn(sample="'bool'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): bool
    {
        if (!$request->isPut() && !$request->isPatch()) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        if (!empty($data['priceUnitId']) && !empty($data['content']) && !empty($data['basicUnit'])) {
            return $this->getService('ProductTypePackage')->update($params['entity_id'], $data);
        }

        throw new Exceptions\Error();
    }
}
