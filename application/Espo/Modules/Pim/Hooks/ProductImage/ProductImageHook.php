<?php

namespace Espo\Modules\Pim\Hooks\ProductImage;

use Espo\Modules\Pim\Core\Hooks\AbstractImageHook;
use Espo\ORM\Entity;

/**
 * CategoryImageHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductImageHook extends AbstractImageHook
{
    /**
     * @var string
     */
    protected $entityName = 'ProductImage';

    /**
     * Return condition for query
     *
     * @param Entity $entity
     *
     * @return array
     */
    protected function getCondition(Entity $entity)
    {
        return ['productId' => $entity->get('productId')];
    }
}
