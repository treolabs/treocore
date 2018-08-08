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

use Espo\Modules\TreoCore\Services\ModuleManager as ModuleManagerService;
use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;
use Espo\Core\Utils\Json;

/**
 * ModuleManager controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ModuleManager extends Base
{

    /**
     * @ApiDescription(description="Get modules")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ModuleManager/list")
     * @ApiReturn(sample="{
     *     'total': 1,
     *     'list': [
     *           {
     *               'id': 'Revisions',
     *               'name': 'Revisions',
     *               'version': '1.0.0',
     *               'description': 'Module Revisions for TreoCRM.',
     *               'required': [],
     *               'isActive': true
     *           }
     *       ]
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionList($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getModuleManagerService()->getList();
    }

    /**
     * @ApiDescription(description="Install module")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ModuleManager/installModule")
     * @ApiBody(sample="{
     *     'id': 'Erp',
     *     'version': '1.0.0' - not required
     * }")
     * @ApiReturn(sample="'bool'")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionInstallModule($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!empty($data['id'])) {
            // prepare version
            $version = (!empty($data['version'])) ? $data['version'] : null;

            return $this->getModuleManagerService()->installModule($data['id'], $version);
        }

        throw new Exceptions\NotFound();
    }

    /**
     * @ApiDescription(description="Update module version")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/ModuleManager/updateModule")
     * @ApiBody(sample="{
     *     'id': 'Erp',
     *     'version': '1.1.0'
     * }")
     * @ApiReturn(sample="'bool'")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionUpdateModule($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPut()) {
            throw new Exceptions\BadRequest();
        }

        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!empty($data['id']) && !empty($data['version'])) {
            return $this->getModuleManagerService()->updateModule($data['id'], $data['version']);
        }

        throw new Exceptions\NotFound();
    }

    /**
     * @ApiDescription(description="Delete module")
     * @ApiMethod(type="DELETE")
     * @ApiRoute(name="/ModuleManager/deleteModule")
     * @ApiBody(sample="{
     *     'id': 'Erp'
     * }")
     * @ApiReturn(sample="'bool'")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionDeleteModule($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isDelete()) {
            throw new Exceptions\BadRequest();
        }

        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!empty($id = $data['id'])) {
            return $this->getModuleManagerService()->deleteModule($id);
        }

        throw new Exceptions\NotFound();
    }

    /**
     * @ApiDescription(description="Cancel module changes")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ModuleManager/cancel")
     * @ApiBody(sample="{
     *     'id': 'Erp'
     * }")
     * @ApiReturn(sample="'bool'")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionCancel($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!empty($id = $data['id'])) {
            return $this->getModuleManagerService()->cancel($id);
        }

        throw new Exceptions\NotFound();
    }

    /**
     * @ApiDescription(description="Get available modules for install")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ModuleManager/availableModulesList")
     * @ApiReturn(sample="{
     *     'total': 1,
     *     'list': [
     *           {
     *               'id': 'Revisions',
     *               'name': 'Revisions',
     *               'version': '1.0.0',
     *               'description': 'Module Revisions for TreoCRM.',
     *               'status': 'available'
     *           }
     *       ]
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionGetAvailableModulesList($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getModuleManagerService()->getAvailableModulesList();
    }

    /**
     * @ApiDescription(description="Get module manager stream data")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ModuleManager/logs")
     * @ApiReturn(sample="{
     *     'total': 'int',
     *     'list': 'array'
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     */
    public function actionLogs($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getModuleManagerService()->getLogs($request);
    }

    /**
     * Get module manager service
     *
     * @return ModuleManagerService
     */
    protected function getModuleManagerService(): ModuleManagerService
    {
        return $this->getService('ModuleManager');
    }
}
