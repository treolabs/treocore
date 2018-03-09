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
