<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ProductTypePackage controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProductTypePackage extends AbstractProductTypeController
{

    /**
     * @ApiDescription(description="Get package product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/view")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiReturn(sample="'array'")
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionView($params, $data, Request $request): array
    {
        if (!$this->getAcl()->check('Product', 'read')) {
            throw new Exceptions\Forbidden();
        }

        if (!$request->isGet()) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('ProductTypePackage')->getPackageProduct($params['entity_id']);
    }

    /**
     * @ApiDescription(description="Update package product")
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/Markets/ProductTypePackage/{productId}/update")
     * @ApiParams(name="productId", type="string", is_required=1, description="Product ID")
     * @ApiParams(name="priceUnitId", type="string", is_required=1, description="Price Unit ID")
     * @ApiParams(name="content", type="string", is_required=1, description="Content")
     * @ApiParams(name="basicUnit", type="string", is_required=1, description="Basic Unit")
     * @ApiParams(name="packingUnit", type="string", is_required=0, description="Packing Unit")
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
    public function actionUpdate($params, $data, Request $request): bool
    {
        if (!$request->isPut() && !$request->isPatch()) {
            throw new Exceptions\BadRequest();
        }

        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        if (!empty($data['priceUnitId']) && !empty($data['content']) && !empty($data['basicUnit'])) {
            return $this->getService('ProductTypePackage')->update($params['entity_id'], $data);
        }

        throw new Exceptions\Error();
    }
}
