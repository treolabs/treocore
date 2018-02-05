<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions\Forbidden;

/**
 * Class AbstractLockController
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractLockController extends AbstractController
{
    /**
     * Lock action list
     *
     * @param $params
     * @param $data
     * @param $request
     *
     * @return void
     *
     * @throws Forbidden
     */
    public function actionList($params, $data, $request)
    {
        throw new Forbidden();
    }
}
