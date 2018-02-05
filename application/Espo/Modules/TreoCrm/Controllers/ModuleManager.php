<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Modules\TreoCrm\Services\ModuleManager as ModuleManagerService;
use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

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
     * Get module manager service
     *
     * @return ModuleManagerService
     */
    protected function getModuleManagerService(): ModuleManagerService
    {
        return $this->getService('ModuleManager');
    }
}
