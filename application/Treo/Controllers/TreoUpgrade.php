<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;
use Treo\Services\TreoUpgrade as Service;

/**
 * Controller TreoUpgrade
 *
 * @author r.ratsun r.ratsun@treolabs.com
 */
class TreoUpgrade extends \Espo\Core\Controllers\Base
{

    /**
     * @ApiDescription(description="Get available version for Treo upgrade")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/TreoUpgrade/versions")
     * @ApiReturn(sample="[{'version': '1.0.0', 'link': '#'}]")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionVersions($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getUpgradeService()->getVersions();
    }

    /**
     * @ApiDescription(description="Run upgrade TreoCore")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/TreoUpgrade/upgrade")
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
    public function actionUpgrade($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        return $this
            ->getUpgradeService()
            ->createUpgradeJob((!empty($data->version)) ? $data->version : null);
    }

    /**
     * @ApiDescription(description="Get update log")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/TreoUpgrade/action/createUpdateLog")
     * @ApiBody(sample="{'version': '1.0.0'}")
     * @ApiReturn(sample="'true'")
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
    public function actionCreateUpdateLog($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost() || empty($data->version)) {
            throw new Exceptions\BadRequest();
        }

        return $this->getUpgradeService()->createUpdateLog((string)$data->version);
    }

    /**
     * Get upgrade service
     *
     * @return Service
     */
    protected function getUpgradeService(): Service
    {
        return $this->getService('TreoUpgrade');
    }
}
