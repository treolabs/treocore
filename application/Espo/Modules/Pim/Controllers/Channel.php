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

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * Channel controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Channel extends AbstractController
{

    /**
     * Get channel product attributes action
     *
     * @ApiDescription(description="Get channel product attributes")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Channel/{channel_id}/Product/{product_id}/attributes")
     * @ApiParams(name="channel_id", type="string", is_required=1, description="Channel id")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     * 'productAttributeValueId': 'string',
     * 'attributeId': 'string',
     * 'name': 'string',
     * 'type': 'string',
     * 'isRequired': 'bool',
     * 'typeValue': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValueEnUs': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValue other languages ...': [],
     * 'value': 'array|string',
     * 'valueEnUs': 'array|string',
     * 'value other languages ...': 'array|string',
     * 'attributeGroupId': 'string',
     * 'attributeGroupName': 'string',
     * 'attributeGroupOrder': 'int',
     * 'isCustom': 'bool'
     * }]")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionGetChannelProductAttributes($params, $data, Request $request)
    {
        if ($this->isReadAction($request)
            && $this->getAcl()->check('Attribute', 'read')
            && $this->getAcl()->check('Product', 'read')
        ) {
            return $this->getRecordService()->getChannelProductAttributes($params['channel_id'], $params['product_id']);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Get products action
     *
     * @ApiDescription(description="Get products in channel")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Channel/{channel_id}/products")
     * @ApiParams(name="channel_id", type="string", is_required=1, description="Channel id")
     * @ApiReturn(sample="[{
     *     'channelProductId': 'string',
     *     'productId': 'string',
     *     'productName': 'string',
     *     'categories': [
     *          'string - categories name'
     *     ],
     *     'isActive': 'bool',
     *     'isEditable': 'bool'
     *},
     *     '...'
     *]")
     *
     * @param string $channelId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getProducts(string $channelId): array
    {
        if ($this->isReadEntity($this->name, $channelId)
            && $this->getAcl()->check('Product', 'read')
        ) {
            return $this->getRecordService()->getProducts($channelId);
        }

        throw new Exceptions\Forbidden();
    }
}
