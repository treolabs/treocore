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

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;

/**
 * Class of Product
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Product extends AbstractSelectManager
{

    /**
     * Where
     *
     * @param array $where
     * @param array $result
     */
    protected function where($where, &$result)
    {
        // prepare where for fields
        parent::where($this->prepareProductAttributeWhere($where), $result);
    }

    /**
     * Prepare product attribute where
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareProductAttributeWhere(array $data): array
    {
        foreach ($data as $k => $row) {
            if (empty($row['isAttribute']) && is_array($row['value'])) {
                $data[$k]['value'] = $this->prepareProductAttributeWhere($row['value']);
            } elseif (!empty($row['isAttribute'])) {
                // prepare attribute where
                $where = [
                    'type'  => 'and',
                    'value' => [
                        [
                            'type'      => 'equals',
                            'attribute' => 'attributeId',
                            'value'     => $row['attribute']
                        ],
                        [
                            'type'      => $row['type'],
                            'attribute' => 'value',
                            'value'     => $row['value']
                        ]
                    ]
                ];

                // get product ids
                $ids = $this->getProductIds([$where]);

                // prepare product where
                if (!empty($ids)) {
                    $productWhere = [
                        'type'  => 'or',
                        'value' => []
                    ];

                    foreach ($ids as $id) {
                        $productWhere['value'][] = [
                            'type'      => 'equals',
                            'attribute' => 'id',
                            'value'     => $id
                        ];
                    }
                }

                // prepare where clause
                if (empty($productWhere)) {
                    unset($data[$k]);
                } else {
                    $data[$k] = $productWhere;
                }
            }
        }

        return $data;
    }

    /**
     * Get products filtered by attributes
     *
     * @param array $where
     *
     * @return array
     */
    protected function getProductIds(array $where = []): array
    {
        // prepare result
        $result = [];

        // create service
        $service = $this->createService('ProductAttributeValue');

        // get data
        $data = $service->findEntities([
            'where' => $where
        ]);

        if ($data['total'] > 0) {
            foreach ($data['collection'] as $entity) {
                if (!empty($entity->get('product')) && !in_array($entity->get('productId'), $result)) {
                    $result[] = $entity->get('productId');
                }
            }
        }

        return $result;
    }

    /**
     * NotAssociatedProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotAssociatedProducts(&$result)
    {
        // prepare data
        $data = (array) $this->getSelectCondition('notAssociatedProducts');

        if (!empty($data['associationId'])) {
            $associatedProducts = $this->getAssociatedProducts($data['associationId'], $data['mainProductId']);
            foreach ($associatedProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string) $row['related_product_id']
                ];
            }
        }
    }

    /**
     * OnlySimple filter
     *
     * @param array $result
     */
    protected function boolFilterOnlySimple(&$result)
    {
        $result['whereClause'][] = [
            'type' => 'simpleProduct'
        ];
    }

    /**
     * NotConfigurabledProducts filter
     *
     * @param array $result
     */
    protected function boolFilterNotConfigurabledProducts(&$result)
    {
        // prepare data
        $productId = (string) $this->getSelectCondition('notConfigurabledProducts');

        if (!empty($productId)) {
            $variants = $this->getProductVariants($productId);
            foreach ($variants as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string) $id
                ];
            }
        }
    }

    /**
     * NotBundledProducts filter
     *
     * @param array $result
     */
    protected function boolFilterNotBundledProducts(&$result)
    {
        //prepare data
        $productId = (string) $this->getSelectCondition('notBundledProducts');

        if (!empty($productId)) {
            $variants = $this->getBundleItems($productId);
            foreach ($variants as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string) $id
                ];
            }
        }
    }

    /**
     * Get assiciated products
     *
     * @param string $associationId
     * @param string $productId
     *
     * @return array
     */
    protected function getAssociatedProducts($associationId, $productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          related_product_id
        FROM
          association_product
        WHERE
          main_product_id ='.$pdo->quote($productId).'
          AND association_id = '.$pdo->quote($associationId).'
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get product variants
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductVariants($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          product_id
        FROM
          product_type_configurable
        WHERE
          configurable_product_id ='.$pdo->quote($productId).'
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'product_id') : [];
    }

    /**
     * Get bundle items
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getBundleItems($productId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          product_id
        FROM
          product_type_bundle
        WHERE
          bundle_product_id ='.$pdo->quote($productId).'
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'product_id') : [];
    }

    /**
     * NotLinkedWithOrder filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithOrder(&$result)
    {
        $orderId = (string) $this->getSelectCondition('notLinkedWithOrder');

        if (!empty($orderId)) {
            $orderProducts = $this->getOrderProducts($orderId);
            foreach ($orderProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string) $row['product_id']
                ];
            }
        }
    }

    /**
     * Get order products
     *
     * @param string $orderId
     *
     * @return array
     */
    protected function getOrderProducts($orderId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT product_id
                FROM order_product
                WHERE order_id = '.$pdo->quote($orderId);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithChannel filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(&$result)
    {
        $channelId = (string) $this->getSelectCondition('notLinkedWithChannel');

        if (!empty($channelId)) {
            $channelProducts = $this->createService('Channel')->getProducts($channelId);
            foreach ($channelProducts as $row) {
                $result['whereClause'][] = [
                    'id!=' => (string) $row['productId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithBrand filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithBrand(array &$result)
    {
        // prepare data
        $brandId = (string) $this->getSelectCondition('notLinkedWithBrand');

        if (!empty($brandId)) {
            // get Products linked with brand
            $products = $this->getBrandProducts($brandId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with brand
     *
     * @param string $brandId
     *
     * @return array
     */
    protected function getBrandProducts(string $brandId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0 
                      AND brand_id = :brandId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['brandId' => $brandId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithProductFamily filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProductFamily(array &$result)
    {
        // prepare data
        $productFamilyId = (string) $this->getSelectCondition('notLinkedWithProductFamily');

        if (!empty($productFamilyId)) {
            // get Products linked with brand
            $products = $this->getProductFamilyProducts($productFamilyId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * Get productIds related with productFamily
     *
     * @param string $productFamilyId
     *
     * @return array
     */
    protected function getProductFamilyProducts(string $productFamilyId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT id AS productId
                FROM product
                WHERE deleted = 0
                      AND product_family_id = :productFamilyId';

        $sth = $pdo->prepare($sql);
        $sth->execute(['productFamilyId' => $productFamilyId]);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * NotLinkedWithSupplier filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithSupplier(array &$result)
    {
        // prepare data
        $supplierId = (string) $this->getSelectCondition('notLinkedWithSupplier');

        if (!empty($supplierId)) {
            // get Products linked with brand
            $products = $this->createService('Supplier')->getProduct($supplierId);
            foreach ($products as $row) {
                $result['whereClause'][] = [
                    'id!=' => $row['productId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithPackaging filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithPackaging(&$result)
    {
        // find products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where([
                'packagingId' => (string) $this->getSelectCondition('notLinkedWithPackaging')
            ])
            ->find();

        if (!empty($products)) {
            foreach ($products as $product) {
                $result['whereClause'][] = [
                    'id!=' => $product->get('id')
                ];
            }
        }
    }
}
