<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Hooks\Product;

use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Pim\Core\Hooks\AbstractHook;
use Espo\ORM\Entity;

/**
 * ProductHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductHook extends AbstractHook
{

    /**
     * Before save action
     *
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // SKU validation
        if (!$this->isUnique($entity, 'sku')) {
            throw new BadRequest('Product with such SKU already exist');
        }
    }

    /**
     * Product SKU is unique?
     *
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUniqueProduct(Entity $entity): bool
    {
        // prepare result
        $result = true;

        // find product
        $product = $this->getEntityManager()
            ->getRepository('Product')
            ->where(['sku' => $entity->get('sku')])
            ->findOne();

        if (!empty($product) && $product->get('id') != $entity->get('id')) {
            $result = false;
        }

        return $result;
    }
}
