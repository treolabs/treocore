<?php

namespace Espo\Modules\Pim\Traits;

/**
 * CategoryTrait trait
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
trait CategoryChildrenTrait
{

    /**
     * Get all category children by recursive
     *
     * @param string $categoryId
     * @param array  $data
     * @return array
     */
    public function getCategoryChildren(string $categoryId, array $data)
    {
        // get children
        $children = $this->getDbCategoryChildren($categoryId);

        // merge data
        $data = array_merge($data, $children);

        // get children in child
        foreach ($children as $childCategoryId) {
            $data = $this->getCategoryChildren($childCategoryId, $data);
        }

        return $data;
    }

    /**
     * Get category children from DB
     *
     * @param string $categoryId
     * @return array
     */
    protected function getDbCategoryChildren($categoryId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = 'SELECT
          id
        FROM
          category
        WHERE
          category_parent_id ='.$pdo->quote($categoryId).'
          AND is_active = 1
          AND deleted = 0';

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return (!empty($result)) ? array_column($result, 'id') : [];
    }
}
