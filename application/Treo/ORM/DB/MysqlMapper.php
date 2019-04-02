<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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

namespace Treo\ORM\DB;

use Espo\ORM\IEntity;

/**
 * Class MysqlMapper
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class MysqlMapper extends \Espo\ORM\DB\MysqlMapper
{
    use \Treo\Traits\EventManagerTrait;

    /**
     * @inheritdoc
     */
    public function updateRelation(IEntity $entity, $relationName, $id, $columnData)
    {
        // prepare event data
        $e = [
            'entity'       => $entity,
            'relationName' => $relationName,
            'id'           => $id,
            'columnData'   => $columnData
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeUpdateRelation', $e);

        // exec
        $e['result'] = parent::updateRelation($e['entity'], $e['relationName'], $e['id'], $e['columnData']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterUpdateRelation', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function massRelate(IEntity $entity, $relationName, array $params = [])
    {
        // prepare event data
        $e = [
            'entity'       => $entity,
            'relationName' => $relationName,
            'params'       => $params
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeMassRelate', $e);

        // exec
        $e['result'] = parent::massRelate($e['entity'], $e['relationName'], $e['params']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterMassRelate', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function addRelation(IEntity $entity, $relationName, $id = null, $relEntity = null, $data = null)
    {
        // prepare event data
        $e = [
            'entity'       => $entity,
            'relationName' => $relationName,
            'id'           => $id,
            'relEntity'    => $relEntity,
            'data'         => $data
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeAddRelation', $e);

        // exec
        $e['result'] = parent::addRelation($e['entity'], $e['relationName'], $e['id'], $e['relEntity'], $e['data']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterAddRelation', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function removeRelation(IEntity $entity, $relationName, $id = null, $all = false, IEntity $relEntity = null)
    {
        // prepare event data
        $e = [
            'entity'       => $entity,
            'relationName' => $relationName,
            'id'           => $id,
            'all'          => $all,
            'relEntity'    => $relEntity
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeRemoveRelation', $e);

        // exec
        $e['result'] = parent::removeRelation($e['entity'], $e['relationName'], $e['id'], $e['all'], $e['relEntity']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterRemoveRelation', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function insert(IEntity $entity)
    {
        // prepare event data
        $e = [
            'entity' => $entity
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeInsert', $e);

        // exec
        $e['result'] = parent::insert($e['entity']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterInsert', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function update(IEntity $entity)
    {
        // prepare event data
        $e = [
            'entity' => $entity
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeUpdate', $e);

        // exec
        $e['result'] = parent::update($e['entity']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterUpdate', $e);

        return $e['result'];
    }

    /**
     * @inheritdoc
     */
    public function delete(IEntity $entity)
    {
        // prepare event data
        $e = [
            'entity' => $entity
        ];

        // triggered
        $e = $this->getEventManager()->triggered('Mapper', 'beforeDelete', $e);

        // exec
        $e['result'] = parent::delete($e['entity']);

        // triggered
        $this->getEventManager()->triggered('Mapper', 'afterDelete', $e);

        return $e['result'];
    }
}
