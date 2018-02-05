<?php

namespace Espo\Modules\Pim\Acl;

use \Espo\Core\Acl\Base;
use \Espo\Entities\User;
use \Espo\ORM\Entity;

/**
 * Class CategoryImage
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class CategoryImage extends Base
{

    /**
     * Check scope
     *
     * @param User        $user
     * @param array       $data
     * @param string      $action
     *
     * @param Entity|null $entity
     * @param array       $entityAccessData
     *
     * @return bool
     */
    public function checkScope(User $user, $data, $action = null, Entity $entity = null, $entityAccessData = array())
    {
        return $this->getAclManager()->check($user, 'Category', $action);
    }

    /**
     * Check acl entity
     *
     * @param User   $user
     * @param Entity $entity
     * @param array  $data
     * @param string $action
     *
     * @return mixed
     */
    public function checkEntity(User $user, Entity $entity, $data, $action)
    {
        // get category
        $category = $entity->isNew()
            ? $this->getEntityManager()->getEntity('Category', $entity->get('categoryId'))
            : $entity->get('category');

        // check acl for category
        return $this->getAclManager()->check($user, $category, $action);
    }
}
