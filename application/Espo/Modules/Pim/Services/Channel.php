<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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

namespace Espo\Modules\Pim\Services;

use Espo\Modules\Pim\Traits;

/**
 * Service of Channel
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Channel extends AbstractService
{

    use Traits\ProductAttributesTrait;
    use Traits\CategoryChildrenTrait;

    /**
     * Get attributes for product in channel
     *
     * @param string $channelId
     * @param string $productId
     *
     * @return array
     */
    public function getChannelProductAttributes($channelId, $productId)
    {
        $result = [];

        $productAttributes = $this->getProductAttributes($productId);
        if (!empty($productAttributes)) {
            // prepare pdo
            $pdo = $this->getEntityManager()->getPDO();

            $sql = "SELECT
                  cp.attribute_id
                FROM channel_product_attribute_value cp
                WHERE 
                  cp.deleted = 0
                  AND cp.channel_id=" . $pdo->quote($channelId) . "
                  AND cp.product_id=" . $pdo->quote($productId);

            // execute
            $sth = $pdo->prepare($sql);
            $sth->execute();

            $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($data as $row) {
                foreach ($productAttributes as $key => $productAttribute) {
                    if ($productAttribute['attributeId'] == $row['attribute_id']) {
                        unset($productAttributes[$key]);
                    }
                }
            }

            $result = array_values($productAttributes);
        }

        return $this->prepareJsonData($result);
    }

    /**
     * Get product data for channel
     *
     * @param string $channelId
     *
     * @return array
     */
    public function getProducts(string $channelId): array
    {
        // get category products
        $products = array_merge($this->getChannelCategoryProducts($channelId), []);

        // get channel products
        $products = array_merge($this->getDBChannelProducts($channelId), $products);

        // prepare data
        $data = [];
        foreach ($products as $product) {
            // prepare channel product id
            $channelProductId = (isset($product['channelProductId'])) ? (string)$product['channelProductId'] : null;

            // prepare categories
            $categories = [];
            if (isset($data[$product['productId']]['categories'])) {
                $categories = $data[$product['productId']]['categories'];
            }
            if (!empty($product['categoryName'])) {
                $categories[] = $product['categoryName'];
            }

            $data[$product['productId']] = [
                'channelProductId' => $channelProductId,
                'productId'        => (string)$product['productId'],
                'productName'      => (string)$product['productName'],
                'isActive'         => (bool)$product['isActive'],
                'categories'       => $categories,
                'isEditable'       => !is_null($channelProductId),
            ];
        }

        // prepare result
        $result = [];
        foreach ($data as $row) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get channel category products data
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getChannelCategoryProducts(string $channelId): array
    {
        // prepare result
        $products = [];

        // get all categories
        $categories = $this->getDBChannelCategories($channelId);
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $categories = $this->getCategoryChildren($category, $categories);
            }
            $products = $this->getDBCategoriesProducts(array_unique($categories));
        }

        return $products;
    }

    /**
     * Get channel categories from DB
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getDBChannelCategories(string $channelId): array
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "SELECT
                  category_id 
                FROM category_channel_linker
                WHERE deleted = 0 AND channel_id = " . $pdo->quote($channelId);
        $sth = $pdo->prepare($sql);
        $sth->execute();

        $categories = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($categories)) ? array_column($categories, 'category_id') : [];
    }

    /**
     * Get channel categories from DB
     *
     * @param array $categories
     *
     * @return array
     */
    protected function getDBCategoriesProducts(array $categories): array
    {
        // prepare data
        $where      = $this->getAclWhereSql('Category', 'c');
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "SELECT
                  c.id        AS categoryId,
                  c.name      AS categoryName,
                  p.id        AS productId,
                  p.name      AS productName,
                  p.is_active AS isActive
                FROM product_category_linker AS l
                 JOIN category AS c ON c.id = l.category_id
                 JOIN product AS p ON p.id = l.product_id AND p.deleted = 0
                WHERE l.deleted = 0 $where AND l.category_id IN (\"" . implode('","', $categories) . "\")";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get channel products data from DB
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getDBChannelProducts(string $channelId): array
    {
          // prepare data
        $where      = $this->getAclWhereSql('Product', 'p');
        $pdo = $this->getEntityManager()->getPDO();
        
        $sql = "SELECT
                  cp.id       AS channelProductId,
                  p.id        AS productId,
                  p.name      AS productName,
                  p.is_active AS isActive
                FROM channel_product AS cp
                  JOIN product AS p ON p.id = cp.product_id
                WHERE cp.deleted = 0
                      $where
                      AND p.deleted = 0
                      AND cp.channel_id = " . $pdo->quote($channelId);
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Prepare json data
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareJsonData(array $data)
    {
        foreach ($data as $key => $row) {
            // prepare of data types
            $data[$key]['isRequired'] = (bool)$row['isRequired'];
            $data[$key]['attributeId'] = (string)$row['attributeId'];
            $data[$key]['name'] = (string)$row['name'];
            $data[$key]['type'] = (string)$row['type'];
            $data[$key]['attributeGroupId'] = (string)$row['attributeGroupId'];
            $data[$key]['attributeGroupName'] = (string)$row['attributeGroupName'];
            $data[$key]['attributeGroupOrder'] = (int)$row['attributeGroupOrder'];
            $data[$key]['isCustom'] = (bool)$row['isCustom'];

            if (strripos($row['type'], 'array') !== false || strripos($row['type'], 'enum') !== false) {
                foreach ($row as $name => $value) {
                    // prepare data
                    $isValue = strpos($name, 'value');
                    $isMultienum = strripos($row['type'], 'multienum');
                    $isTypeValue = strpos($name, 'typeValue');

                    if ((($isValue !== false && $isMultienum !== false) || $isTypeValue !== false) && !empty($value)) {
                        $data[$key][$name] = json_decode($value);
                    }
                }
            }
        }

        return $data;
    }
}
