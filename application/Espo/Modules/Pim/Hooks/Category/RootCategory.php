<?php

namespace Espo\Modules\Pim\Hooks\Category;

use Espo\Core\Hooks\Base as BaseHook;
use Espo\Modules\Pim\Entities\Category as CategoryEntity;

/**
 * RootCategory hook
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class RootCategory extends BaseHook
{

    /**
     * Update category root after category saved
     *
     * @param CategoryEntity $entity
     * @param array  $params
     */
    public function afterSave(CategoryEntity $entity, $params = [])
    {
        // build tree
        $this->updateCategoryTree($entity, $params);

        // activate parents
        $this->activeteParents($entity, $params);

        // deactivate children
        $this->deactivateChildren($entity, $params);
    }

    /**
     * Update category tree
     *
     * @param CategoryEntity $entity
     * @param array  $params
     */
    protected function updateCategoryTree(CategoryEntity $entity, $params)
    {
        // is has changes
        if (empty($params['isSaved']) && ($entity->isAttributeChanged('categoryParentId') || $entity->isNew())) {
            // prepare root
            $root = $entity;

            // get parent root
            $parent = $entity->get('categoryParent');

            if (!empty($parent)) {
                $categoryRoot = $parent->get('categoryRoot');
                $root         = (empty($categoryRoot)) ? $parent : $categoryRoot;
            }

            // set parent root to current entity
            $entity->set('categoryRootId', $root->get('id'));
            $entity->set('categoryRootName', $root->get('name'));

            $this->saveEntity($entity);

            // update all children
            if (!$entity->isNew()) {
                $children = $this->getEntityChildren($entity->get('categories'), []);
                foreach ($children as $child) {
                    $child->set('categoryRootId', $root->get('id'));
                    $child->set('categoryRootName', $root->get('name'));

                    $this->saveEntity($child);
                }
            }
        }
    }

    /**
     * Activate parents categories if it needs
     *
     * @param CategoryEntity $entity
     * @param array  $params
     */
    protected function activeteParents(CategoryEntity $entity, $params)
    {
        // is activate action
        $isActivate = $entity->isAttributeChanged('isActive') && $entity->get('isActive');

        if (empty($params['isSaved']) && $isActivate && !$entity->isNew()) {
            // update all parents
            foreach ($this->getEntityParents($entity, []) as $parent) {
                $parent->set('isActive', true);
                $this->saveEntity($parent);
            }
        }
    }

    /**
     * Deactivate children categories if it needs
     *
     * @param CategoryEntity $entity
     * @param array  $params
     */
    protected function deactivateChildren(CategoryEntity $entity, $params)
    {
        // is deactivate action
        $isDeactivate = $entity->isAttributeChanged('isActive') && !$entity->get('isActive');

        if (empty($params['isSaved']) && $isDeactivate && !$entity->isNew()) {
            // update all children
            $children = $this->getEntityChildren($entity->get('categories'), []);
            foreach ($children as $child) {
                $child->set('isActive', false);
                $this->saveEntity($child);
            }
        }
    }

    /**
     * Save entity
     *
     * @param CategoryEntity $entity
     */
    protected function saveEntity(CategoryEntity $entity)
    {
        $this->getEntityManager()->saveEntity($entity, ['isSaved' => true]);
    }

    /**
     * Get entity parents
     *
     * @param CategoryEntity $category
     * @param array $parents
     *
     * @return array
     */
    protected function getEntityParents(CategoryEntity $category, array $parents): array
    {
        $parent = $category->get('categoryParent');
        if (!empty($parent)) {
            $parents[] = $parent;
            $parents   = $this->getEntityParents($parent, $parents);
        }

        return $parents;
    }

    /**
     * Get all children by recursive
     *
     * @param array $entities
     * @param array $children
     * @return array
     */
    protected function getEntityChildren($entities, array $children)
    {
        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $children[] = $entity;
            }
            foreach ($entities as $entity) {
                $children = $this->getEntityChildren($entity->get('categories'), $children);
            }
        }

        return $children;
    }
}
