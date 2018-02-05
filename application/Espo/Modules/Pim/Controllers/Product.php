<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * Product controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Product extends AbstractController
{

    /**
     * Get item in products action
     *
     * @ApiDescription(description="Get item in products")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Product/{product_id}/itemInProducts")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     *     'total': 'integer',
     *     'list': 'array'
     * }]")
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\Error
     */
    public function actionGetItemInProducts($params, $data, Request $request): array
    {
        if ($this->isReadAction($request, $params) && isset($params['product_id'])) {
            return $this->getRecordService()->getItemInProducts((string) $params['product_id'], $request);
        }

        throw new Exceptions\Error();
    }

    /**
     * Get attributes action
     *
     * @ApiDescription(description="Get attributes in product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Product/{product_id}/attributes")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     * 'productAttributeValueId': 'string',
     * 'attributeId': 'string',
     * 'name': 'string',
     * 'type': 'string',
     * 'isRequired': 'bool',
     * 'typeValue': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValueEnUs': [
     *   'string',
     *   'string',
     *   '...'
     * ],
     * 'typeValue other languages ...': [],
     * 'value': 'array|string',
     * 'valueEnUs': 'array|string',
     * 'value other languages ...': 'array|string',
     * 'attributeGroupId': 'string',
     * 'attributeGroupName': 'string',
     * 'attributeGroupOrder': 'int',
     * 'isCustom': 'bool'
     * }]")
     *
     * @param string $productId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getAttributes(string $productId): array
    {
        if ($this->isReadEntity($this->name, $productId)) {
            return $this->getRecordService()->getAttributes($productId);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Get channelAttributes action
     *
     * @ApiDescription(description="Get channelAttributes in product")
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Product/{product_id}/channelAttributes")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     * 'channelId': 'string',
     * 'channelName': 'string',
     * 'attributes': [{
     *     'channelProductAttributeValueId': 'string',
     *     'attributeId': 'string',
     *     'attributeName': 'string',
     *     'attributeType': 'string',
     *     'attributeRequired': 'bool',
     *     'attributeTypeValue': [
     *          'string',
     *          '...'
     *     ],
     *     'attributeTypeValueEnUs':[
     *          'string',
     *          '...'
     *     ],
     *     'attributeTypeValue Other Languages':[
     *          'string',
     *          '...'
     *     ],
     *     'attributeValue': 'array|string',
     *     'attributeValueEnUs':'array|string',
     *     'attributeValue Other Languages':'array|string',
     *     'attributeIsMultiChannel': 'bool',
     *     'attributeGroupId'   : 'string',
     *     'attributeGroupName': 'string',
     *     'attributeGroupOrder': 'int'
     * }]}]")
     *
     * @param string $productId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getChannelAttributes(string $productId): array
    {
        // is granted ?
        $isGrantedChannel = $this->getAcl()->check('Channel', 'read');
        $isGrantedAttribute = $this->getAcl()->check('Attribute', 'read');

        if ($this->isReadEntity($this->name, $productId) && $isGrantedChannel && $isGrantedAttribute) {
            return $this->getRecordService()->getChannelAttributes($productId);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Update Attributes for product action
     *
     * @ApiDescription(description="Update Attributes for product")*
     * @ApiMethod(type="PUT")
     * @ApiRoute(name="/Markets/Product/{product_id}/attributes")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiParams(name="attributes", type="json/array", is_required=1, description="Attribute data")
     * @ApiBody(sample="[{
     *     'attributeId': 'string',
     *     'value': 'string|array',
     *     'valueEnUs': 'string|array',
     *     'value Other Languages': 'string|array'
     * },{}]")
     * @ApiReturn(sample="'bool'")
     *
     * @param string $productId
     * @param array  $data
     *
     * @return bool
     * @throws Exceptions\Forbidden
     */
    public function updateAttributes(string $productId, array $data): bool
    {
        if ($this->isEditEntity($this->name, $productId)) {
            return $this->getRecordService()->updateAttributes($productId, $data);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Get Supplier in product
     *
     * @ApiDescription(description="Get Supplier in product")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Product/{product_id}/supplier")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     *     'supplierProductId': 'string',
     *     'supplierId': 'string',
     *     'supplierName': 'string'
     * },{}]")
     *
     * @param string $productId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getSupplier(string $productId): array
    {
        if ($this->isReadEntity($this->name, $productId)) {
            return $this->getRecordService()->getSupplier($productId);
        }

        throw new Exceptions\Forbidden();
    }

    /**
     * Get Channels action
     *
     * @ApiDescription(description="Get Channels in product")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/Markets/Product/{product_id}/channels")
     * @ApiParams(name="product_id", type="string", is_required=1, description="Product id")
     * @ApiReturn(sample="[{
     *     'channelProductId': 'string',
     *     'channelId': 'string',
     *     'channelName': 'string',
     *     'categories': [
     *          'string',
     *          'string',
     *          '...'
     *      ],
     *     'isActive': 'bool',
     *     'isEditable': 'bool'
     * },{}]")
     *
     * @param string $productId
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function getChannels(string $productId): array
    {
        if ($this->isReadEntity($this->name, $productId) && $this->getAcl()->check('Channel', 'read')) {
            return $this->getRecordService()->getChannels($productId);
        }

        throw new Exceptions\Forbidden();
    }
}
