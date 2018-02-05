<?php

namespace Espo\Modules\Pim\Hooks\ProductFamily;

use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;
use Espo\Core\Utils\Util;
use Espo\Core\Exceptions\Forbidden;

/**
 * ProductFamilyHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ProductFamilyHook extends BaseHook
{

    /**
     * After Save Entity hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterSave(Entity $entity, $options = [])
    {
        $this->setParentAttributes($entity);
    }

    /**
     * Before remove Entity hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeRemove(Entity $entity, $options = [])
    {
        $this->checkIsSystem($entity);
    }

    /**
     * After remove Entity hook
     *
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        // unlink related products
        $this->unlinkProducts($entity->get('id'));
    }

    /**
     * Set attributes from parent ProductFamily
     *
     * @param Entity $entity
     */
    protected function setParentAttributes(Entity $entity)
    {
        if (!$entity->get('isSystem') &&
            ($entity->isNew())
        ) {
            $attributeList = $this->getEntityManager()
                ->getEntity('ProductFamily', $entity->get('productFamilyTemplateId'))
                ->get('productFamilyAttributes');

            foreach ($attributeList as $familyAttribute) {
                $familyAttributeNew = clone $familyAttribute;
                $familyAttributeNew->set([
                    'id'              => Util::generateId(),
                    'productFamilyId' => $entity->get('id')
                ]);
                $familyAttributeNew->setIsNew(true);
                $this->getEntityManager()->saveEntity($familyAttributeNew);
            }
        }
    }

    /**
     * Check if Entity is system
     *
     * @param Entity $entity
     *
     * @throws Forbidden
     */
    protected function checkIsSystem(Entity $entity)
    {
        if ($entity->get('isSystem')) {
            throw new Forbidden();
        }
    }

    /**
     * Unlink related products
     *
     * @param string $id
     */
    protected function unlinkProducts($id)
    {
        // prepare pdo
        $pdo = $this->getEntityManager()->getPDO();

        // prepare sql
        $sql = "UPDATE product SET product_family_id = NULL WHERE product_family_id = ".$pdo->quote($id);

        // execute
        $sth = $pdo->prepare($sql);
        $sth->execute();
    }
}
