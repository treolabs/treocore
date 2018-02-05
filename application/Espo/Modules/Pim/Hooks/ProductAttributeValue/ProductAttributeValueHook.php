<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Hooks\ProductAttributeValue;

use Espo\Modules\Pim\Core\Hooks\AbstractHook;
use Espo\ORM\Entity;

/**
 * ProductAttributeValueHook hook
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProductAttributeValueHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        // update product complete
        $this->updateProductComplete($entity);
    }

    /**
     * Update product complete
     *
     * @param Entity $entity
     *
     * @return void
     */
    protected function updateProductComplete(Entity $entity): void
    {
        // get product with complete updates
        $product = $this->createService('Completeness')->updateCompleteness($entity->get('product'), false);

        // save product
        $this->getEntityManager()->saveEntity($product);
    }
}
