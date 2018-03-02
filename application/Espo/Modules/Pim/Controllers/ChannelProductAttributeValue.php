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

declare(strict_types=1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Utils\Json;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ChannelProductAttributeValue controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ChannelProductAttributeValue extends AbstractTechnicalController
{
    /**
     * Action update
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return mixed
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): bool
    {
        // prepare data
        $data = Json::decode(Json::encode($data), true);

        // check Request
        if (!$this->isValidUpdateAction($params, $data, $request)) {
            throw new Exceptions\BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        // update Data
        return $this
            ->getService('ChannelProductAttributeValue')
            ->updateValue($params['id'], $data);
    }

    /**
     * Action create
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionCreate($params, $data, Request $request)
    {
        // check Request
        if (!$this->isValidCreateAction($data, $request)) {
            throw new BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        // Crate value
        $result = $this
            ->getService('ChannelProductAttributeValue')
            ->createValue($data);

        return empty($result) ? false : true ;
    }

    /**
     * Delete value
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionDelete($params, $data, Request $request): bool
    {
        // check Request
        if (!$this->isValidDeleteAction($params, $request)) {
            throw new BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        return $this
            ->getService('ChannelProductAttributeValue')
            ->deleteValue($params['id']);
    }
}
