<?php

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * Category controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Category extends AbstractController
{

    /**
     * Update action
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return type
     * @throws Exceptions\Error
     */
    public function actionUpdate($params, $data, $request)
    {
        if ($this->isEditAction($request, $params['id'])) {
            if (!empty($data['categoryParentId'])
                && $this->getRecordService()->isChildCategory($params['id'], $data['categoryParentId'])
            ) {
                throw new Exceptions\Error('You can not choose a child category');
            }

            return parent::actionUpdate($params, $data, $request);
        }

        throw new Exceptions\Error();
    }
}
