<?php

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
        // separate attribute from where and prepare it
        $attributeWhere = [];
        foreach ($where as $k => $row) {
            if (!empty($row['isAttribute'])) {
                $attributeWhere[] = $row;
                unset($where[$k]);
            }
        }

        // prepare where for fields
        parent::where($where, $result);

        // add product attribute filter
        if (!empty($attributeWhere)) {
            $data = [];
            foreach ($this->getProductIds($this->prepareAttributeWhere($attributeWhere)) as $id) {
                $data[] = [
                    'id=' => $id
                ];
            }

            // push any for empty result
            if (empty($data)) {
                $data[] = [
                    'id=' => 'no_such_id'
                ];
            }

            // prepare where clause
            $result['whereClause'][] = [
                'OR' => $data
            ];
        }
    }

    /**
     * Prepare attribute where
     *
     * @param array $rows
     *
     * @return array
     */
    protected function prepareAttributeWhere(array $rows): array
    {
        foreach ($rows as $k => $row) {
            foreach ($row['value'] as $value) {
                $rows[$k]['value'] = $this->prepareAttributeWhere($row['value']);
            }

            if (isset($row['attribute']) && empty($row['isAttribute'])) {
                $rows[$k] = [
                    'type'  => 'and',
                    'value' => [
                        [
                            'isAttribute' => true,
                            'type'        => 'equals',
                            'attribute'   => 'attributeId',
                            'value'       => $row['attribute']
                        ],
                        [
                            'isAttribute' => true,
                            'type'        => $row['type'],
                            'attribute'   => 'value',
                            'value'       => $row['value']
                        ]
                    ]
                ];
            }
        }

        return $rows;
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

        if (!empty($data['collection'])) {
            foreach ($data['collection'] as $entity) {
                if (!in_array($entity->get('productId'), $result)) {
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
