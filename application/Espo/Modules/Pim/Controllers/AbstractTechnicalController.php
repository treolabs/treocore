<?php
declare(strict_types=1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Controllers\Base;
use Slim\Http\Request;

/**
 * AbstractTechController controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractTechnicalController extends Base
{

    /**
     * Action update
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionPatch($params, $data, Request $request): bool
    {
        return $this->actionUpdate($params, $data, $request);
    }


    /**
     * Check create action
     *
     * @param         $data
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidCreateAction($data, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        if (!$request->isPost() || !is_array($data)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check read action
     *
     * @param         $params
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidReadAction($params, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        if (!is_array($params) || empty($params['id']) || !$request->isGet()) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check update action
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidUpdateAction($params, $data, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        $isParams = is_array($params) && !empty($params['id']);

        if ((!$request->isPut() && !$request->isPatch()) || !$isParams || !is_array($data)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check delete action
     *
     * @param         $params
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidDeleteAction($params, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        $isParams = is_array($params) && !empty($params['id']);

        if (!$request->isDelete() || !$isParams) {
            $result = false;
        }

        return $result;
    }
}
