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

namespace Treo\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;
use Treo\Services\TreoUpgrade as Service;

/**
 * Controller TreoUpgrade
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class TreoUpgrade extends Base
{

    /**
     * @ApiDescription(description="Get available version for Treo upgrade")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/TreoUpgrade/availableVersion")
     * @ApiReturn(sample="{'version': '1.0.0'}")
     *
     * @return array
     */
    public function actionAvailableVersion($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return ['version' => $this->getUpgradeService()->getAvailableVersion()];
    }

    /**
     * @ApiDescription(description="Run upgrade TreoCore")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/TreoUpgrade/upgrade")
     * @ApiReturn(sample="'bool'")
     *
     * @return bool
     */
    public function actionUpgrade($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getUpgradeService()->createUpgradeJob();
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