<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
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
     * @ApiRoute(name="/TreoUpgrade/action/Upgrade")
     * @ApiBody(sample="{'version': '1.0.0'}")
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

        if (!$request->isPost() || empty($data->version)) {
            throw new Exceptions\BadRequest();
        }

        return $this
            ->getUpgradeService()
            ->runUpgrade((string)$data->version);
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
