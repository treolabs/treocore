<?php

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ProductFamily controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductFamily extends AbstractController
{

    /**
     * Error to remove system entities
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     * @return bool
     * @throws Exceptions\Error
     */
    public function actionDelete($params, $data, $request)
    {
        // Get entity
        $entity = $this->getEntityManager()->getEntity($this->name, $data['id']);

        if (isset($entity) && $entity->get('isSystem')) {
            throw new Exceptions\Error("Can't remove system Product Family");
        } else {
            return parent::actionDelete($params, $data, $request);
        }
    }

    /**
     * Error to remove system entities
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     * @throws Exceptions\Error
     */
    public function actionMassDelete($params, $data, $request)
    {
        if ($this->isMassDeleteAction($request)) {
            if (!isset($data['ids']) || $this->getRecordService()->hasSystemProductFamily($data['ids'])) {
                throw new Exceptions\Error("Can't remove system Product Family");
            } else {
                return parent::actionMassDelete($params, $data, $request);
            }
        }

        throw new Exceptions\Error();
    }

    /**
     * Get duplicate attributes
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     * @return array
     */
    public function postActionGetDuplicateAttributes($params, $data, $request)
    {
        // get result
        $result = parent::postActionGetDuplicateAttributes($params, $data, $request);

        // set system
        $result['isSystem'] = false;

        return $result;
    }

    /**
     * Get Attributes action
     *
     * @ApiDescription(description="Get Attributes in ProductFamily")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/ProductFamily/{product_family_id}/attributes")
     * @ApiParams(name="product_family_id", type="string", is_required=1, description="ProductFamily id")
     * @ApiReturn(sample="[{
     *     'productFamilyAttributeId': 'string',
     *     'isMultiChannel': 'bool',
     *     'isRequired': 'bool',
     *     'attributeId': 'string',
     *     'attributeName': 'string',
     *     'attributeType': 'string',
     *     'attributeGroupId': 'string',
     *     'attributeGroupName': 'string'
     * },{}]")
     *
     * @param string $productFamilyId
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function getAttributes(string $productFamilyId)
    {
        if ($this->isReadEntity($this->name, $productFamilyId)) {
            return $this->getRecordService()->getAttributes($productFamilyId);
        }

        throw new Exceptions\Error();
    }
}
