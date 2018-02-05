<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

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

        return $this->getService('ProgressManager')->isShowPopup();
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
