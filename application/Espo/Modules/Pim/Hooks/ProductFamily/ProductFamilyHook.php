<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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
