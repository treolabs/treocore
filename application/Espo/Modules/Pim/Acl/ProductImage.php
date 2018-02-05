<?php

namespace Espo\Modules\Pim\Acl;

use \Espo\Core\Acl\Base;
use \Espo\Entities\User;
use \Espo\ORM\Entity;

/**
 * Class ProductImage
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductImage extends Base
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
        return $this->getAclManager()->check($user, 'Product', $action);
    }

    /**
     * Check acl entity
     *
     * @param User   $user
     * @param Entity $entity
     * @param array  $data
     * @param string $action
     *
     * @return bool
     */
    public function checkEntity(User $user, Entity $entity, $data, $action)
    {
        // get product
        $product = $entity->isNew()
            ? $this->getEntityManager()->getEntity('Product', $entity->get('productId'))
            : $entity->get('product');

        // check acl for product
        return $this->getAclManager()->check($user, $product, $action);
    }
}
