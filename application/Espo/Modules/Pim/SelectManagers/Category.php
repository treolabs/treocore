<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\SelectManagers;

use Espo\Modules\Pim\Core\SelectManagers\AbstractSelectManager;
use Espo\Modules\Pim\Traits\CategoryChildrenTrait;

/**
 * Class of Category
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Category extends AbstractSelectManager
{

    use CategoryChildrenTrait;

    /**
     * NotChildCategory filter
     *
     * @param array $result
     */
    protected function boolFilterNotChildCategory(array &$result)
    {
        // prepare data
        $categoryId = (string) $this->getSelectCondition('notChildCategory');

        $this->hideChildCategories($result, $categoryId);
    }

    /**
     * NotLinkedWithChannel filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithChannel(array &$result)
    {
        // prepare data
        $channelId = (string) $this->getSelectCondition('notLinkedWithChannel');

        if (!empty($channelId)) {
            // get categories linked with channel
            $channelCategories = $this->getChannelCategories($channelId);
            foreach ($channelCategories as $category) {
                $this->hideChildCategories($result, $category['categoryId']);

                $result['whereClause'][] = [
                    'id!=' => (string) $category['categoryId']
                ];
            }
        }
    }

    /**
     * NotLinkedWithProduct filter
     *
     * @param array $result
     */
    protected function boolFilterNotLinkedWithProduct(array &$result)
    {
        // prepare data
        $productId = (string) $this->getSelectCondition('notLinkedWithProduct');

        if (!empty($productId)) {
            foreach ($this->getProductCategories($productId) as $id) {
                $result['whereClause'][] = [
                    'id!=' => (string) $id
                ];
            }
        }
    }

    /**
     * Get product categories
     *
     * @param string $productId
     *
     * @return array
     */
    protected function getProductCategories(string $productId): array
    {
        // prepare result
        $result = [];

        $sql = "SELECT
                  category_id AS categoryId
                FROM
                  product_category_linker
                WHERE
                  deleted=0 AND product_id='$productId'";

        $sth  = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();
        $data = $sth->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($data)) {
            $result = array_column($data, 'categoryId');
        }

        return $result;
    }

    /**
     * Get Channel Categories
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getChannelCategories(string $channelId): array
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT chl.category_id AS categoryId
                FROM 
                  category_channel_linker AS chl
                JOIN 
                  category AS c ON chl.category_id = c.id
                WHERE 
                  chl.deleted = 0 
                  AND c.deleted = 0
                  AND c.is_active = 1
                  AND chl.channel_id = '.$pdo->quote($channelId);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Hide all subcategories
     *
     * @param array  $result
     * @param string $categoryId
     */
    protected function hideChildCategories(array &$result, string $categoryId)
    {
        $children = $this->getCategoryChildren($categoryId, []);

        if (!empty($children)) {
            foreach ($children as $childCategoryId) {
                $result['whereClause'][] = [
                    'id!=' => (string) $childCategoryId
                ];
            }
        }
    }
}
