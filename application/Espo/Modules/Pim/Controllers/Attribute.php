<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * Attribute controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Attribute extends AbstractController
{

    /**
     * @ApiDescription(description="Get filters data for product entity")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Attribute/filtersData")
     * @ApiReturn(sample="'json'")
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Error
     * @throws Exceptions\Forbidden
     */
    public function actionGetFiltersData($params, $data, Request $request): array
    {
        if ($this->isReadAction($request, $params)) {
            return $this->getService('Attribute')->getFiltersData();
        }

        throw new Exceptions\Error();
    }
}
