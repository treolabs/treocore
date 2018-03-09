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

namespace Espo\Modules\Pim\Core\Repositories;

use \Espo\Core\Templates\Repositories\Base;
use \Espo\ORM\EntityCollection;
use \Espo\ORM\Entity;

/**
 * AbstractRepositories
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class AbstractRepositories extends Base
{

    /**
     * Find linked Entities with the use of custom relation
     *
     * @param string $link         name of the related entities
     * @param array  $selectParams
     *
     * @return EntityCollection
     */
    public function findCustomLinkedEntities(string $link, array $selectParams = []): EntityCollection
    {
        $pdo = $this->getPDO();
        $query = $this->getEntityManager()->getQuery();
        // get sql query
        $sql = $query->createSelectQuery($link, $selectParams);
        // execute
        $sth = $pdo->query($sql);
        $result = $sth->fetchAll();

        return new EntityCollection($result, $link, $this->entityFactory);
    }


    /**
     * Get the total number of entities using a custom relation
     *
     * @param string $link         name of the related entities
     * @param array  $selectParams
     *
     * @return int
     */
    public function getCustomTotal(string $link, array $selectParams = []): int
    {
        // set select
        $selectParams['select'] = ['COUNT:id'];
        // remove limitation
        unset($selectParams['limit'], $selectParams['offset']);
        $pdo = $this->getPDO();
        $query = $this->getEntityManager()->getQuery();
        // get sql query
        $sql = $query->createSelectQuery($link, $selectParams);
        // execute
        $ps = $pdo->query($sql);
        $result = $ps->fetchColumn();

        return (int)$result;
    }

    /**
     * Call beforeUnrelate hook method
     *
     * @param Entity $entity
     * @param        $relationName
     * @param        $foreign
     * @param array  $options
     */
    protected function beforeUnrelate(Entity $entity, $relationName, $foreign, array $options = array())
    {
        parent::beforeUnrelate($entity, $relationName, $foreign, $options);

        if ($foreign instanceof Entity) {
            $foreignEntity = $foreign;
            if (!$this->hooksDisabled) {
                $hookData = array(
                    'relationName'  => $relationName,
                    'foreignEntity' => $foreignEntity
                );
                $this->getEntityManager()->getHookManager()->process(
                    $this->entityType,
                    'beforeUnrelate',
                    $entity,
                    $options,
                    $hookData
                );
            }
        }
    }
}
