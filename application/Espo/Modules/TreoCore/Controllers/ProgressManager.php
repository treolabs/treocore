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

namespace Espo\Modules\TreoCore\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ProgressManager controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManager extends Base
{

    /**
     * @ApiDescription(description="Is need to show progress popup")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Progress/isShowPopup")
     * @ApiReturn(sample="'true'")
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionIsShowPopup($params, $data, Request $request): bool
    {
        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        // get data
        $data = $this
            ->getContainer()
            ->get('progressManager')
            ->getPopupData();

        // get user id
        $userId = $this->getUser()->get('id');

        return (!empty($data) && in_array("'$userId'", $data));
    }


    /**
     * @ApiDescription(description="Set popup as showed for user")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/Progress/popupShowed")
     * @ApiReturn(sample="'true'")
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionpopupShowed($params, $data, Request $request): bool
    {
        if (!$request->isPost() || empty($userId = $data->userId)) {
            throw new Exceptions\BadRequest();
        }

        $this
            ->getContainer()
            ->get('progressManager')
            ->hidePopup((string)$userId);

        return true;
    }

    /**
     * @ApiDescription(description="Get data for progresses popup")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Progress/popupData")
     * @ApiReturn(sample="{
     *     'total': 1,
     *     'list': [
     *           {
     *               'id': '112233',
     *               'name': 'Export. Arterbuy products',
     *               'progress': 25,
     *               'status': {
     *                 'key': 'in_progress',
     *                 'translate': 'In progress'
     *               },
     *               'actions': [
     *                 {
     *                   'type': 'cancel',
     *                   'data': {}
     *                 }
     *               ]
     *           }
     *       ]
     * }")
     *
     * @return array
     * @throws Exceptions\BadRequest
     */
    public function actionPopupData($params, $data, Request $request): array
    {
        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('ProgressManager')->popupData($request);
    }

    /**
     * @ApiDescription(description="Cancel action")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Progress/:id/cancel")
     * @ApiReturn(sample="true")
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionCancel($params, $data, Request $request): bool
    {
        if (!$request->isPut() || empty($params['id'])) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('CancelStatusAction')->cancel($params['id']);
    }

    /**
     * @ApiDescription(description="Close action")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Progress/:id/close")
     * @ApiReturn(sample="true")
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionClose($params, $data, Request $request): bool
    {
        if (!$request->isPut() || empty($params['id'])) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('CloseStatusAction')->close($params['id']);
    }
}
