<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Modules\TreoCrm\Services\ModuleManager as ModuleManagerService;
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
     * @ApiDescription(description="Update module activation. If 1 then 0, if 0 then 1.")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/ModuleManager/:moduleId/updateActivation")
     * @ApiParams(name="moduleId", type="string", is_required=1, description="Module ID")
     * @ApiReturn(sample="true")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionUpdateActivation($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPut()) {
            throw new Exceptions\BadRequest();
        }

        if (!empty($moduleId = $params['moduleId'])) {
            return $this->getModuleManagerService()->updateActivation($moduleId);
        }

        throw new Exceptions\NotFound();
    }

    /**
     * @ApiDescription(description="Install module")
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ModuleManager/installModule")
     * @ApiBody(sample="{
     *     'id': 'Erp'
     * }")
     * @ApiReturn(sample="{
     *     'status': 'true',
     *     'output': 'some text from composer'
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionInstallModule($params, $data, Request $request): array
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
            return $this->getModuleManagerService()->installModule($data['id']);
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
     * @ApiReturn(sample="{
     *     'status': 'true',
     *     'output': 'some text from composer'
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionUpdateModule($params, $data, Request $request): array
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
     * @ApiDescription(description="Get composer user")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ModuleManager/composerUser")
     * @ApiReturn(sample="{
     *     'username': 'test',
     *     'password': 'qwerty'
     * }")
     *
     * @return array
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionGetComposerUser($params, $data, Request $request): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getModuleManagerService()->getComposerUser();
    }

    /**
     * @ApiDescription(description="Set composer user")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/ModuleManager/composerUser")
     * @ApiBody(sample="{
     *     'username': 'test',
     *     'password': 'qwerty'
     * }")
     * @ApiReturn(sample="true")
     *
     * @return bool
     * @throws Exceptions\Forbidden
     * @throws Exceptions\BadRequest
     * @throws Exceptions\NotFound
     */
    public function actionSetComposerUser($params, $data, Request $request): bool
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isPut()) {
            throw new Exceptions\BadRequest();
        }

        // prepare data
        $data = Json::decode(Json::encode($data), true);

        if (!empty($data['username']) && !empty($data['password'])) {
            return $this->getModuleManagerService()->setComposerUser($data['username'], $data['password']);
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
     * Get module manager service
     *
     * @return ModuleManagerService
     */
    protected function getModuleManagerService(): ModuleManagerService
    {
        return $this->getService('ModuleManager');
    }
}
