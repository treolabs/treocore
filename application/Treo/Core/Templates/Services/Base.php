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

declare(strict_types=1);

namespace Treo\Core\Templates\Services;

/**
 * Class Base
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Base extends \Espo\Services\Record
{
    /**
     * Add relation to entities
     *
     * @param array  $ids
     * @param array  $foreignIds
     * @param string $link
     *
     * @return bool
     */
    public function addRelation(array $ids, array $foreignIds, string $link): bool
    {
        $result = false;

        $foreignEntityType = $this->getEntityManager()
            ->getEntity($this->entityType)
            ->getRelationParam($link, 'entity');

        $foreignEntities = $this->getEntityManager()
            ->getRepository($foreignEntityType)
            ->where(['id' => $foreignIds])->find();

        $entities = $this->getRepository()
            ->where(['id' => $ids])->find();

        foreach ($entities as $entity) {
            foreach ($foreignEntities as $foreignEntity) {
                if ($this->getRepository()->relate($entity, $link, $foreignEntity) && !$result) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Remove relation from entities
     *
     * @param array  $ids
     * @param array  $foreignIds
     * @param string $link
     *
     * @return bool
     */
    public function removeRelation(array $ids, array $foreignIds, string $link): bool
    {
        $result = false;

        $foreignEntityType = $this->getEntityManager()
            ->getEntity($this->entityType)
            ->getRelationParam($link, 'entity');

        $foreignEntities = $this->getEntityManager()
            ->getRepository($foreignEntityType)
            ->where(['id' => $foreignIds])->find();

        $entities = $this->getRepository()
            ->where(['id' => $ids])->find();

        foreach ($entities as $entity) {
            foreach ($foreignEntities as $foreignEntity) {
                if ($this->getRepository()->unrelate($entity, $link, $foreignEntity) && !$result) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
