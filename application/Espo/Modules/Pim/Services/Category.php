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

use Espo\Modules\Pim\Core\Services\AbstractService;
use Espo\Modules\Pim\Traits\CategoryChildrenTrait;
use \Espo\ORM\EntityCollection;

/**
 * Service of Category
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Category extends AbstractService
{

    use CategoryChildrenTrait;

    /**
     * Is child category
     *
     * @param string $categoryId
     * @param string $selectedCategoryId
     *
     * @return bool
     */
    public function isChildCategory(string $categoryId, string $selectedCategoryId): bool
    {
        return in_array($selectedCategoryId, $this->getCategoryChildren($categoryId, []));
    }

    /**
     * Find linked Products for Category
     *
     * @param string $parentCategoryId current category
     * @param array  $params           select params
     *
     * @return array
     */
    public function findLinkedEntitiesProducts(string $parentCategoryId, array $params): array
    {
        $link = 'Product';
        // get children categories
        $categoriesId = $this->getCategoryChildren($parentCategoryId, []);
        $categoriesId['parent'] = $parentCategoryId;
        // set custom join
        $customJoin = "JOIN (SELECT DISTINCT `pcl`.`product_id` 
                                        FROM `product_category_linker` AS `pcl`
                                        WHERE 
                                            `pcl`.`deleted` = 0 
                                            AND `pcl`.`category_id` IN ('" . implode("', '", $categoriesId) . "'))
                                        AS link ON `link`.`product_id` = `product`.`id`";

        $data = $this->findCustomLinkedEntities($link, $params, $customJoin);

        return [
            'total' => $data['total'],
            'list'  => $this->setCategoriesToProducts($data['collection'], $categoriesId)
        ];
    }

    /**
     * Set categories for products
     *
     * @param EntityCollection $products
     * @param array            $categoriesId children categories and parent category Id
     *
     * @return array
     */
    protected function setCategoriesToProducts(EntityCollection $products, array $categoriesId): array
    {
        $pdo = $this->getEntityManager()->getPDO();
        // select categories links with products
        $sql = "SELECT
                  pcl.product_id,
                  pcl.category_id,
                  cat.name
                FROM product_category_linker AS pcl
                  JOIN category AS cat ON cat.id = pcl.category_id
                WHERE pcl.deleted = 0 AND pcl.category_id IN ('" . implode("', '", $categoriesId) . "')";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $categories = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = $products->toArray();
        // set categories
        foreach ($result as $key => $product) {
            foreach ($categories as $catKey => $categoryVal) {
                if ($product['id'] === $categoryVal['product_id']) {
                    $result[$key]['categories'][] = (string)$categoryVal['name'];
                    // if this current category relate with this product - set isEditable
                    if ($categoryVal['category_id'] == $categoriesId['parent'] || $result[$key]['isEditable']) {
                        $result[$key]['isEditable'] = true;
                    } else {
                        $result[$key]['isEditable'] = false;
                    }
                    unset($categories[$catKey]);
                }
            }
        }

        return $result;
    }
}
