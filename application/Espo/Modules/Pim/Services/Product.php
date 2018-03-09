<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Modules\Pim\Entities\Product as ProductEntity;
use Espo\Modules\Pim\Traits;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Espo\Core\Utils\Json;
use Slim\Http\Request;
use \PDO;

/**
 * Service of Product
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Product extends AbstractService
{

    use Traits\ProductAttributesTrait;

    protected $duplicatingLinkList = [
        'categories',
        'attributes',
        'productAttributeValues',
        'channelProducts',
        'channelProductAttributeValues',
        'productImages',
        'supplierProducts',
        'bundleProducts',
        'associationMainProducts',
        'productTypePackages',
        'productConfigurables',
    ];

    /**
     * Set duplicating links
     *
     * @param array $links
     */
    public function setDuplicatingLinkList(array $links)
    {
        $this->duplicatingLinkList = array_merge($this->duplicatingLinkList, $links);
    }

    /**
     * Get item in products data
     *
     * @param string  $productId
     * @param Request $request
     *
     * @return array
     */
    public function getItemInProducts(string $productId, Request $request): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        // get total
        $total = $this->getDbCountItemInProducts($productId);

        if (!empty($total)) {
            // prepare result
            $result = [
                'total' => $total,
                'list'  => $this->getDbItemInProducts($productId, $request)
            ];
        }

        return $result;
    }

    /**
     * Get entity
     *
     * @param string $id
     *
     * @return ProductEntity
     * @throws Forbidden
     */
    public function getEntity($id = null)
    {
        // get entity
        $entity = parent::getEntity($id);

        if (empty($entity->get('amount'))) {
            $entity->set('amount', 0);
        }

        return $entity;
    }

    /**
     * Find entities
     *
     * @param array $params
     *
     * @return array
     */
    public function findEntities($params)
    {
        // get entity
        $data = parent::findEntities($params);

        foreach ($data['collection'] as $key => $entity) {
            if (empty($entity->get('amount'))) {
                $data['collection'][$key]->set('amount', 0);
            }
        }

        return $data;
    }

    /**
     * Get Supplier for Product
     *
     * @param string $productId
     *
     * @return array
     */
    public function getSupplier(string $productId): array
    {
        $suppliers = $this->getSupplierProduct($productId);

        return $suppliers;
    }

    /**
     * Get Product Attributes
     *
     * @param $productId
     *
     * @return array
     */
    public function getAttributes($productId)
    {
        return $this->formatAttributeData($this->getProductAttributes($productId));
    }

    /**
     * Get Channel product attributes
     *
     * @param string $productId
     *
     * @return array
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getChannelAttributes(string $productId): array
    {
        $result     = [];
        $categories = $this->getCategories($productId);

        $channelList = $this->getDBChannel($productId, $categories, true);

        // get data from db
        $data = $this->getAttributesForChannel($productId, array_column($channelList, 'channelId'));

        $value     = $this->getMultiLangName('attribute_value');
        $typeValue = $this->getMultiLangName('attribute_type_value');

        //Merge Channel and Attributes
        foreach ($channelList as $channel) {
            //Check whether the channel has already been
            if (!isset($result[$channel['channelId']])) {
                $result[$channel['channelId']] = [
                    'channelId'   => $channel['channelId'],
                    'channelName' => $channel['channelName'],
                    'attributes'  => []
                ];
            }
            //Add attributes
            foreach ($data as $key => $attribute) {
                if ($channel['channelId'] === $attribute['channelId']) {
                    // check if exists channel value
                    $issetChannelValue = !is_null($attribute['channelProductAttributeValueId']);

                    // create new ChannelProductAttributeValue if attribute is multichannel and value not exists
                    if (!$issetChannelValue && (bool)$attribute['attributeIsMultiChannel']) {
                        // create new ChannelProductAttributeValue
                        $attribute['channelProductAttributeValueId'] = $this
                            ->createChannelProductAttributeValue($productId, $channel['channelId'], $attribute);
                    }

                    unset($attribute['channelId']);
                    // get row
                    $row = $this->prepareAttributeValue($attribute, $value, $typeValue, 'attribute');
                    unset($data[$key]);

                    //Prepare attribute
                    $result[$channel['channelId']]['attributes'][] = $row;
                }
            }
        }

        return array_values($result);
    }

    /**
     * Update attribute value
     *
     * @param $productId
     * @param $post
     *
     * @return bool
     */
    public function updateAttributes($productId, $post)
    {
        // prepare result
        $result = false;

        // prepare data
        $data = Json::decode(Json::encode($post), true);

        if ($this->isValidAttributesData($data)) {
            // get all AttributeValues for this Product
            $attributeValList = $this->getEntityManager()
                ->getRepository('ProductAttributeValue')
                ->where(['productId' => $productId])
                ->find();

            // keys collection
            $attributeCount = $attributeValList->count();
            $attributeKeys  = $attributeCount > 0 ? range(0, $attributeCount - 1) : [];

            // get new ProductAttributeValue and set ProductId
            $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
            $productAttributeValue->set(['productId' => $productId]);

            $multiLangValue = $this->getMultiLangName('value');

            foreach ($data as $postAttributeVal) {
                // serialize value to jsonArray
                foreach ($multiLangValue as $value) {
                    if (is_array($postAttributeVal[$value['alias']])) {
                        $postAttributeVal[$value['alias']] = Json::encode($postAttributeVal[$value['alias']]);
                    }
                }

                // update existing attributeValue
                foreach ($attributeKeys as $row) {
                    $attributeVal = $attributeValList->offsetGet($row);
                    if ($attributeVal->get('attributeId') === $postAttributeVal['attributeId']) {
                        // handle audited attribute
                        $this
                            ->getProductAttributeValueService()
                            ->handleAuditedAttribute($attributeVal, $postAttributeVal, $productId);

                        // set data
                        $attributeVal->set($postAttributeVal);

                        // save
                        $this->getEntityManager()->saveEntity($attributeVal);
                        unset($attributeKeys[$row]);
                        continue 2;
                    }
                }
                // create new ProductAttributeValue
                $attributeValue = clone $productAttributeValue;
                $attributeValue->set($postAttributeVal);

                // handle audited attribute
                $this
                    ->getProductAttributeValueService()
                    ->handleAuditedAttribute($attributeValue, $postAttributeVal, $productId);

                $this->getEntityManager()->saveEntity($attributeValue);
            }

            // prepare result
            $result = true;
        }

        return $result;
    }

    /**
     * Get Channels for product
     *
     * @param string $productId
     *
     * @return array
     */
    public function getChannels(string $productId): array
    {
        $categories = $this->getCategories($productId);

        return $this->prepareGetChannels($this->getDBChannel($productId, $categories));
    }

    /**
     * Get ids all active categories in tree
     *
     * @param string $productId
     *
     * @return array
     */
    public function getCategories(string $productId): array
    {
        $result       = [];
        $categoryList = $this->getDBRelatedCategories($productId);
        foreach ($categoryList as $categoryId) {
            $result = $this->getCategoryTree($categoryId, $result);
        }

        return array_unique($result);
    }

    /**
     * Get channels from DB
     *
     * @param string $productId
     *
     * @param array  $categories
     *
     * @param bool   $onlyActive
     *
     * @return array
     */
    protected function getDBChannel(string $productId, array $categories = [], bool $onlyActive = false): array
    {
        // prepare where
        $where = $onlyActive ? ' AND c.is_active = 1' : '';
        $where .= $this->getAclWhereSql('Channel', 'c');

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  cp.id                       AS channelProductId,
                  c.id                        AS channelId,
                  c.name                      AS channelName,
                  c.currencies                AS channelCurrencies,
                  c.is_active                 AS isActive,
                  cl.categoryName             AS categoryName,
                  IF(cp.id IS NOT NULL, 1, 0) AS isEditable
                FROM channel AS c
                  LEFT JOIN channel_product AS cp
                    ON c.id = cp.channel_id AND cp.product_id = ".$pdo->quote($productId)." AND cp.deleted = 0
                  LEFT JOIN (SELECT DISTINCT
                               chl.channel_id AS channel_id,
                               cat.name AS categoryName,
                               cat.id AS categoryId
                             FROM
                               category_channel_linker AS chl
                               JOIN category As cat ON chl.category_id = cat.id AND cat.deleted = 0
                             WHERE
                               chl.category_id IN ('".implode("','", $categories)."')
                               AND chl.deleted = 0
                            ) AS cl
                    ON cl.channel_id = c.id
                WHERE c.deleted = 0
                      AND (cp.id IS NOT NULL OR cl.channel_id IS NOT NULL)".$where;
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $productId
     * @param string $channelId
     * @param array  $attributeData
     *
     * @return string
     * @throws BadRequest
     * @throws Forbidden
     */
    protected function createChannelProductAttributeValue(
        string $productId,
        string $channelId,
        array $attributeData
    ): string {
        /** @var ChannelProductAttributeValue $service */
        $service = $this->getServiceFactory()->create('ChannelProductAttributeValue');

        // prepare data
        $data = [
            'productId' => $productId,
            'channelId' => $channelId,
            'attributeId' => $attributeData['attributeId']
        ];

        return $service->createValue($data, false);
    }

    /**
     * Prepare data Channels for output
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareGetChannels(array $data): array
    {
        $result = [];
        foreach ($data as $key => $row) {
            if (!isset($result[$row['channelId']])) {
                $result[$row['channelId']] = [
                    'channelProductId' => (string) $row['channelProductId'],
                    'channelId'        => (string) $row['channelId'],
                    'channelName'      => (string) $row['channelName'],
                    'isActive'         => (bool) $row['isActive'],
                    'isEditable'       => (bool) $row['isEditable'],
                    'categories'       => []
                ];
            }
            if (!empty($row['categoryName'])) {
                $result[$row['channelId']]['categories'][] = $row['categoryName'];
            }
        }

        return array_values($result);
    }

    /**
     * Get channel product attributes
     *
     * @param string $productId
     *
     * @param array  $channelIds
     *
     * @return array
     */
    protected function getAttributesForChannel(string $productId, array $channelIds): array
    {
        // prepare where
        $where = '';
        $where .= $this->getAclWhereSql('Attribute', 'at');

        // prepare pdo
        $pdo = $this->getEntityManager()->getPDO();

        // prepare multiLang fields
        $multiLangFields     = $this->getMultiLangName('value');
        $multiLangTypeValues = $this->getMultiLangName('type_value');
        $values              = '';
        $typeValues          = '';
        foreach ($multiLangFields as $key => $row) {
            $values     .= ', '.'cpav.'.$row['db_field'].' AS attribute'.ucfirst($row['alias']);
            $typeValues .= ', at.'
                .$multiLangTypeValues[$key]['db_field']
                .' AS attribute'
                .ucfirst($multiLangTypeValues[$key]['alias']);
        }

        // prepare sql
        $sql = "SELECT
                  cpav.id as channelProductAttributeValueId,
                  ch.id as channelId,
                  at.id as attributeId,
                  at.name as attributeName,
                  at.type as attributeType,
                  pfa.is_required as attributeIsRequired,
                  pfa.is_multi_channel as attributeIsMultiChannel
                  ".$typeValues."
                  ".$values."
                FROM attribute AS at
                  JOIN product AS p ON p.id = ".$pdo->quote($productId)." AND p.deleted = 0
                  LEFT JOIN product_attribute_linker AS pal
                    ON at.id = pal.attribute_id AND p.id = pal.product_id AND pal.deleted = 0
                  LEFT JOIN product_family_attribute AS pfa
                    ON pfa.attribute_id = at.id
                    AND pfa.product_family_id = p.product_family_id
                    AND pfa.deleted = 0
                  LEFT JOIN product_family AS pf ON pf.id = p.product_family_id AND pf.deleted = 0
                  LEFT JOIN channel AS ch ON ch.id in  ('".implode("','", $channelIds)."') AND ch.deleted = 0
                  LEFT JOIN channel_product_attribute_value AS cpav
                    ON cpav.product_id = p.id 
                        AND cpav.attribute_id = at.id 
                        AND cpav.deleted = 0
                        AND cpav.channel_id = ch.id
                  LEFT JOIN attribute_group AS ag ON ag.id = at.attribute_group_id AND ag.deleted = 0
                WHERE (cpav.id IS NOT NULL
                       AND (pal.id IS NOT NULL
                       OR (pfa.id IS NOT NULL AND pf.id IS NOT NULL)))
                      OR (pfa.is_multi_channel = 1
                          AND pfa.id IS NOT NULL
                          AND pf.id IS NOT NULL)" . $where;

        // execute
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check is valid data for attribute
     *
     * @param array $data
     *
     * @return bool
     * @throws BadRequest
     */
    protected function isValidAttributesData(array $data): bool
    {
        if (empty($data)) {
            throw new BadRequest;
        }
        foreach ($data as $attributeData) {
            if (empty($attributeData['attributeId'])) {
                throw new BadRequest;
            }
        }

        return true;
    }

    /**
     * Remove ProductAttributeValue from DB
     *
     * @param array $attributeValueId
     */
    protected function cleanProductAttributesValue($attributeValueId)
    {
        // get pdo
        $pdo               = $this->getEntityManager()->getPDO();
        //Prepare AttributeValueId
        $attributeDeleteId = implode(', ', array_map([$pdo, 'quote'], $attributeValueId));

        // prepare sql
        $sql = "DELETE FROM product_attribute_value".
            " WHERE id IN (".$attributeDeleteId.")";
        // execute sql
        $pdo->query($sql);
    }

    /**
     * Return formatted attribute data for get actions
     *
     * @param $data
     *
     * @return array
     */
    protected function formatAttributeData($data)
    {
        // MultiLang fields name
        $multiLangValue     = $this->getMultiLangName('value');
        $multiLangTypeValue = $this->getMultiLangName('type_value');

        foreach ($data as $key => $attribute) {
            //Prepare attribute
            $data[$key] = $this->prepareAttributeValue($attribute, $multiLangValue, $multiLangTypeValue);
        }

        return $data;
    }

    /**
     * Prepare attribute data
     *
     * @param array  $attribute
     * @param array  $multiLangValue
     * @param array  $multiLangTypeValue
     * @param string $prefix
     *
     * @return array
     */
    protected function prepareAttributeValue($attribute, $multiLangValue, $multiLangTypeValue, $prefix = '')
    {
        $type       = 'type';
        $isRequired = 'isRequired';

        if (!empty($prefix)) {
            $type       = $prefix.ucfirst($type);
            $isRequired = $prefix.ucfirst($isRequired);
        }
        $attribute[$isRequired] = (bool) $attribute[$isRequired];
        $value                  = $multiLangValue[0]['alias'];
        $typeValue              = $multiLangTypeValue[0]['alias'];
        switch ($attribute[$type]) {
            case 'int':
                $attribute[$value]     = !is_null($attribute[$value]) ? (int) $attribute[$value] : null;
                break;
            case 'bool':
                $attribute[$value]     = !is_null($attribute[$value]) ? (bool) $attribute[$value] : null;
                break;
            case 'float':
                $attribute[$value]     = !is_null($attribute[$value]) ? (float) $attribute[$value] : null;
                break;
            case 'multiEnum':
            case 'array':
                $attribute[$value]     = !is_null($attribute[$value]) ? json_decode($attribute[$value]) : [];
                $attribute[$typeValue] = !is_null($attribute[$typeValue]) ? json_decode($attribute[$typeValue]) : null;
                break;
            case 'enum':
                $attribute[$typeValue] = !is_null($attribute[$typeValue]) ? json_decode($attribute[$typeValue]) : [];
                break;
            // Serialize MultiLang fields
            case 'multiEnumMultiLang':
            case 'arrayMultiLang':
                foreach ($multiLangValue as $key => $field) {
                    if (!is_null($attribute[$field['alias']])) {
                        $attribute[$field['alias']] = json_decode($attribute[$field['alias']]);
                    } else {
                        $attribute[$field['alias']] = [];
                    }

                    $feild = $multiLangTypeValue[$key]['alias'];
                    if (!is_null($attribute[$feild])) {
                        $attribute[$feild] = json_decode($attribute[$feild]);
                    } else {
                        $attribute[$feild] = null;
                    }
                }
                break;
            case 'enumMultiLang':
                foreach ($multiLangTypeValue as $field) {
                    if (!is_null($attribute[$field['alias']])) {
                        $attribute[$field['alias']] = json_decode($attribute[$field['alias']]);
                    } else {
                        $attribute[$field['alias']] = [];
                    }
                }
                break;
        }

        if (isset($attribute['isCustom'])) {
            $attribute['isCustom'] = (bool) $attribute['isCustom'];
        }
        if (isset($attribute['attributeGroupOrder'])) {
            $attribute['attributeGroupOrder'] = (int) $attribute['attributeGroupOrder'];
        }
        // prepare isMultiChannel
        if (isset($attribute['attributeIsMultiChannel'])) {
            $attribute['attributeIsMultiChannel'] = (bool) $attribute['attributeIsMultiChannel'];
        }

        // prepare attribute group
        if (empty($attribute['attributeGroupId'])) {
            $attribute['attributeGroupId']    = 'no_group';
            $attribute['attributeGroupName']  = 'No group';
            $attribute['attributeGroupOrder'] = 999;
        }

        return $attribute;
    }

    /**
     * Get Supplier for Product from DB
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getSupplierProduct($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  sp.id  AS supplierProductId,
                  s.id   AS supplierId,
                  s.name AS supplierName
                FROM
                  supplier_product AS sp
                  JOIN
                  supplier AS s ON s.id = sp.supplier_id
                WHERE
                  sp.deleted = 0
                  AND s.deleted = 0
                  AND s.is_active = 1
                  AND sp.product_id = ".$pdo->quote($productId);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Save data to db
     *
     * @param Entity $entity
     * @param array  $data
     *
     * @return Entity
     * @throws Error
     */
    protected function save(Entity $entity, $data)
    {
        $entity->set($data);
        if ($this->storeEntity($entity)) {
            $this->prepareEntityForOutput($entity);

            return $entity;
        }

        throw new Error();
    }

    /**
     * Walking through the tree of the category up
     * and return categories id
     *
     * @param string $categoryId
     * @param array  $data
     *
     * @return array
     */
    public function getCategoryTree(string $categoryId, array $data = []): array
    {
        $data[]           = $categoryId;
        $parentCategoryId = $this->getDBParentCategory($categoryId);

        foreach ($parentCategoryId as $parentId) {
            $data = $this->getCategoryTree($parentId, $data);
        }

        return $data;
    }

    /**
     * Get active parent category id for category from DB
     *
     * @param $categoryId
     *
     * @return array
     */
    protected function getDBParentCategory(string $categoryId): array
    {
        $pdo    = $this->getEntityManager()->getPDO();
        $sql    = "SELECT
                  c.category_parent_id AS categoryId
                FROM category AS c
                  JOIN 
                  category AS c2 on c2.id = c.category_parent_id AND c2.deleted = 0 AND c2.is_active = 1
                WHERE 
                c.deleted = 0 AND c.is_active = 1 AND c.id =".$pdo->quote($categoryId).";";
        $sth    = $pdo->prepare($sql);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'categoryId') : [];
    }

    /**
     * Get active related categories for product from DB
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getDBRelatedCategories(string $productId): array
    {
        $pdo    = $this->getEntityManager()->getPDO();
        $sql    = "SELECT c.id AS categoryId
                FROM category AS c
                  JOIN 
                  product_category_linker AS pcl ON pcl.deleted = 0 AND c.id = pcl.category_id
                WHERE c.deleted = 0 
                      AND c.is_active = 1 
                      AND pcl.product_id = ".$pdo->quote($productId).";";
        $sth    = $pdo->prepare($sql);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'categoryId') : [];
    }

    /**
     * Get DB count of item in products data
     *
     * @param string $productId
     *
     * @return int
     */
    protected function getDbCountItemInProducts(string $productId): int
    {
        // prepare data
        $pdo   = $this->getEntityManager()->getPDO();
        $where = $this->getAclWhereSql('Product', 'p');

        // prepare SQL
        $sql = "SELECT
                  COUNT(p.id) as count
                FROM
                  product AS p
                WHERE
                 p.deleted = 0
                AND (
                  p.id IN (SELECT bundle_product_id FROM product_type_bundle
                                                    WHERE product_id = ".$pdo->quote($productId)." $where)
                 OR
                  p.id IN (SELECT configurable_product_id FROM product_type_configurable
                                                          WHERE product_id = ".$pdo->quote($productId)." $where)
                )";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        // get DB data
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);

        return (isset($data[0]['count'])) ? (int) $data[0]['count'] : 0;
    }

    /**
     * Get DB count of item in products data
     *
     * @param string  $productId
     * @param Request $request
     *
     * @return array
     */
    protected function getDbItemInProducts(string $productId, Request $request): array
    {
        // prepare data
        $limit      = (int) $request->get('maxSize');
        $offset     = (int) $request->get('offset');
        $sortOrder  = ($request->get('asc') == 'true') ? 'ASC' : 'DESC';
        $sortColumn = (in_array($request->get('sortBy'), ['name', 'type'])) ? $request->get('sortBy') : 'name';
        $where      = $this->getAclWhereSql('Product', 'p');

        // prepare PDO
        $pdo = $this->getEntityManager()->getPDO();

        // prepare SQL
        $sql = "SELECT
                  p.id   AS id,
                  p.name AS name,
                  p.type AS type
                FROM
                  product AS p
                WHERE
                 p.deleted = 0
                AND (
                  p.id IN (SELECT bundle_product_id FROM product_type_bundle
                                                    WHERE product_id = ".$pdo->quote($productId)." $where)
                 OR
                  p.id IN (SELECT configurable_product_id FROM product_type_configurable
                                                          WHERE product_id = ".$pdo->quote($productId)." $where)
                )
                ORDER BY p.".$sortColumn." ".$sortOrder."
                LIMIT ".$limit." OFFSET ".$offset;
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * After delete action
     *
     * @param Entity $entity
     *
     * @return void
     */
    protected function afterDelete(Entity $entity): void
    {
        $this->deleteProductTypes([$entity->get('id')]);
    }

    /**
     * After mass delete action
     *
     * @param array $idList
     *
     * @return void
     */
    protected function afterMassRemove(array $idList): void
    {
        $this->deleteProductTypes($idList);
    }

    /**
     * Delete product types
     *
     * @param array $idList
     *
     * @return void
     */
    protected function deleteProductTypes(array $idList): void
    {
        // delete type configurable
        $this->getServiceFactory()->create('ProductTypeConfigurable')->deleteByProductId($idList);

        // delete type bundle
        $this->getServiceFactory()->create('ProductTypeBundle')->deleteByProductId($idList);

        // delete type package
        $this->getServiceFactory()->create('ProductTypePackage')->deleteByProductId($idList);
    }

    /**
     * Find linked AssociationMainProduct
     *
     * @param string $id
     * @param array  $params
     *
     * @return array
     * @throws Forbidden
     */
    protected function findLinkedEntitiesAssociationMainProducts(string $id, array $params): array
    {
        // check acl
        if (!$this->getAcl()->check('Association', 'read')) {
            throw new Forbidden();
        }

        // prepare result
        $result = ['list' => [], 'total' => 0];

        // get where part by acl
        $wherePart = '';
        $wherePart .= $this->getAclWhereSql('Association', 'association');
        $wherePart .= $this->getAclWhereSql('Product', 'relatedProduct');

        $result['list']  = $this->getDBAssociationMainProducts($id, $wherePart, $params);
        $result['total'] = $this->getDBTotalAssociationMainProducts($id, $wherePart);

        return $result;
    }

    /**
     * Get AssociationMainProducts from DB
     *
     * @param string $productId
     * @param string $wherePart
     * @param array  $params
     *
     * @return array
     */
    protected function getDBAssociationMainProducts(string $productId, string $wherePart, array $params): array
    {
        // prepare limit
        $limit = '';
        if (!empty($params['maxSize'])) {
            $limit = ' LIMIT '.(int) $params['maxSize'];
            $limit .= ' OFFSET '.(empty($params['offset']) ? 0 : (int) $params['offset']);
        }

        //prepare sort
        $sortOrder   = ($params['asc'] === true) ? 'ASC' : 'DESC';
        $orderColumn = ['relatedProduct', 'association'];
        $sortColumn  = in_array($params['sortBy'], $orderColumn) ? $params['sortBy'].'.name' : 'relatedProduct.name';

        // prepare query
        $sql = "SELECT
                  ap.id,
                  ap.association_id   AS associationId,
                  association.name    AS associationName,
                  p_main.id           AS mainProductId,
                  p_main.name         AS mainProductName,
                  relatedProduct.id   AS relatedProductId,
                  relatedProduct.name AS relatedProductName
                FROM association_product AS ap
                  JOIN product AS relatedProduct 
                    ON relatedProduct.id = ap.related_product_id AND relatedProduct.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 
                  AND  ap.main_product_id = :id "
            .$wherePart
            ."ORDER BY ".$sortColumn." ".$sortOrder
            .$limit;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total AssociationMainProducts
     *
     * @param string $productId
     * @param string $wherePart
     *
     * @return int
     */
    protected function getDBTotalAssociationMainProducts(string $productId, string $wherePart): int
    {
        // prepare query
        $sql = "SELECT
                  COUNT(ap.id)                  
                FROM association_product AS ap
                  JOIN product AS p_rel 
                    ON p_rel.id = ap.related_product_id AND p_rel.deleted = 0
                  JOIN product AS p_main 
                    ON p_main.id = ap.related_product_id AND p_main.deleted = 0
                  JOIN association 
                    ON association.id = ap.association_id AND association.deleted = 0
                WHERE ap.deleted = 0 AND  ap.main_product_id = :id ".$wherePart;

        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute([':id' => $productId]);

        return (int) $sth->fetchColumn();
    }

    /**
     * Get ProductAttributeValue service
     *
     * @return ProductAttributeValue
     */
    protected function getProductAttributeValueService(): ProductAttributeValue
    {
        return $this->getServiceFactory()->create('ProductAttributeValue');
    }

    /**
     * Duplicate links for product
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     */
    protected function duplicateLinks(Entity $product, Entity $duplicatingProduct)
    {
        $repository = $this->getRepository();

        foreach ($this->getDuplicatingLinkList() as $link) {
            $methodName = 'duplicateLinks' . ucfirst($link);
            // check if method exists for duplicate this $link
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($product, $duplicatingProduct);
            } else {
                // find liked entities
                foreach ($repository->findRelated($duplicatingProduct, $link) as $linked) {
                    switch ($product->getRelationType($link)) {
                        case Entity::HAS_MANY:
                            // create and relate new entity
                            $this->linkCopiedEntity($product, $link, $linked);

                            break;
                        case Entity::MANY_MANY:
                            // create new relation
                            $repository->relate($product, $link, $linked);

                            break;
                    }
                }
            }
        }
    }

    /**
     * Get Duplicating Link List
     *
     * @return array
     */
    protected function getDuplicatingLinkList(): array
    {
        $this->getEntityManager()->getContainer()->get('eventManager')->triggered(
            'Product',
            'getDuplicatingLinkList',
            ['productService' => $this]
        );

        return $this->duplicatingLinkList;
    }

    /**
     * Create new entity from $linked entity and relate to Product
     *
     * @param Entity $product
     * @param string $link
     * @param Entity $linked
     */
    protected function linkCopiedEntity(Entity $product, string $link, Entity $linked)
    {
        // get new Entity
        $newEntity = $this->getEntityManager()->getEntity($linked->getEntityType());

        // prepare data
        $data =  [
            '_duplicatingEntityId' => $linked->get('id'),
            'id' => null,
            $product->getRelationParam($link, 'foreignKey') => $product->get('id')
        ];

        // set data to new entity
        $newEntity->set(array_merge($linked->toArray(), $data));
        // save entity
        $this->getEntityManager()->saveEntity($newEntity);
    }

    /**
     * Duplicate ChannelProductAttributeValue
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     *
     * @throws BadRequest
     * @throws Forbidden
     */
    protected function duplicateLinksChannelProductAttributeValues(Entity $product, Entity $duplicatingProduct)
    {
        $attributeService = $this->getServiceFactory()->create('ChannelProductAttributeValue');

        foreach ($this->getChannelAttributes($duplicatingProduct->get('id')) as $row) {
            foreach ($row['attributes'] as $attribute) {
                $data = [
                    'attributeId' => $attribute['attributeId'],
                    'channelId'   => $row['channelId'],
                    'productId'   => $product->get('id')
                ];

                // set value
                if (isset($attribute['attributeValue'])) {
                    $data['value'] = $attribute['attributeValue'];
                }

                // set value multiLang
                foreach ($this->getConfig()->get('inputLanguageList') as $language) {
                    $lang = strtolower($language);
                    if (isset($attribute['attributeValue' . $lang])) {
                        $data['value' . $lang] = $attribute['attributeValue' . $lang];
                    }
                }

                $attributeService->createValue($data);
            }
        }
    }

    /**
     * Duplicate AssociationMainProducts
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     *
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     */
    protected function duplicateLinksAssociationMainProducts(Entity $product, Entity $duplicatingProduct)
    {
        /** @var AssociationProduct $associationProductService */
        $associationProductService = $this->getServiceFactory()->create('AssociationProduct');

        // find AssociationProducts
        $associationProducts = $this->findLinkedEntitiesAssociationMainProducts($duplicatingProduct->get('id'), []);

        foreach ($associationProducts['list'] as $associationProduct) {
            // prepare data
            $data = [
                'mainProductId' => $product->get('id'),
                'relatedProductId' => $associationProduct['relatedProductId'],
                'associationId' => $associationProduct['associationId']
            ];
            // create new AssociationProducts
            $associationProductService->createAssociationProduct($data);
        }
    }

    /**
     * Duplicate BundleProducts
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     *
     */
    protected function duplicateLinksBundleProducts(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('type') === 'bundleProduct') {
            /** @var ProductTypeBundle $bundleService */
            $bundleService = $this->getServiceFactory()->create('ProductTypeBundle');

            // find bundles
            $bundles = $bundleService->getBundleProducts($duplicatingProduct->get('id'));
            // create new bundles
            foreach ($bundles as $bundle) {
                $bundleService->create($product->get('id'), $bundle['productId'], $bundle['amount']);
            }
        }
    }

    /**
     * Duplicate ProductTypePackages
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     *
     */
    protected function duplicateLinksProductTypePackages(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('type') === 'packageProduct') {
            /** @var ProductTypePackage $productPackageService */
            $productPackageService = $this->getServiceFactory()->create('ProductTypePackage');

            // find ProductPackage
            $productPackage = $productPackageService->getPackageProduct($duplicatingProduct->get('id'));

            // create new productPackage
            if (!is_null($productPackage['id'])) {
                $productPackageService->update($product->get('id'), $productPackage);
            }
        }
    }

    /**
     * Duplicate ProductConfigurables
     *
     * @param Entity $product
     * @param Entity $duplicatingProduct
     *
     */
    protected function duplicateLinksProductConfigurables(Entity $product, Entity $duplicatingProduct)
    {
        if ($duplicatingProduct->get('type') === 'configurableProduct') {
            /** @var ProductTypeConfigurable $productConfigurableService */
            $productConfigurableService = $this->getServiceFactory()->create('ProductTypeConfigurable');

            // find ProductPackage
            $variants = $productConfigurableService->getVariantProduct($duplicatingProduct->get('id'));

            foreach ($variants as $variant) {
                $productConfigurableService->create($product->get('id'), $variant['productId']);
            }
        }
    }
}
