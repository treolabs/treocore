<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Listeners;

use Espo\ORM\Entity as OrmEntity;

/**
 * Class Entity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Entity extends AbstractListener
{
    /**
     * @param array $event
     *
     * @return array
     */
    public function beforeSave(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'beforeSave', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process($event['entityType'], 'beforeSave', $event['entity'], $event['options']);
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function afterSave(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'afterSave', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process($event['entityType'], 'afterSave', $event['entity'], $event['options']);
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function beforeRemove(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'beforeRemove', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process($event['entityType'], 'beforeRemove', $event['entity'], $event['options']);
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function afterRemove(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'afterRemove', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process($event['entityType'], 'afterRemove', $event['entity'], $event['options']);
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function beforeMassRelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'beforeMassRelate', $event);

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function afterMassRelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'afterMassRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $hookData = [
                'relationName'   => $event['relationName'],
                'relationParams' => $event['relationParams']
            ];
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process($event['entityType'], 'afterMassRelate', $event['entity'], $event['options'], $hookData);
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function beforeRelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'beforeRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $foreign = $event['foreign'];
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity($event['entity'], $event['relationName'], (string)$foreign);
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event['relationName'],
                    'relationData'  => $event['relationData'],
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process($event['entityType'], 'beforeRelate', $event['entity'], $event['options'], $hookData);
            }
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function afterRelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'afterRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $foreign = $event['foreign'];
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity($event['entity'], $event['relationName'], (string)$foreign);
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event['relationName'],
                    'relationData'  => $event['relationData'],
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process($event['entityType'], 'afterRelate', $event['entity'], $event['options'], $hookData);
            }
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function beforeUnrelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'beforeUnrelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $foreign = $event['foreign'];
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity($event['entity'], $event['relationName'], (string)$foreign);
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event['relationName'],
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process($event['entityType'], 'beforeUnrelate', $event['entity'], $event['options'], $hookData);
            }
        }

        return $event;
    }

    /**
     * @param array $event
     *
     * @return array
     */
    public function afterUnrelate(array $event): array
    {
        // dispatch an event
        $this->dispatch($event['entityType'] . 'Entity', 'afterUnrelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event['hooksDisabled']) && empty($event['options']['skipHooks'])) {
            $foreign = $event['foreign'];
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity($event['entity'], $event['relationName'], (string)$foreign);
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event['relationName'],
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process($event['entityType'], 'afterUnrelate', $event['entity'], $event['options'], $hookData);
            }
        }

        return $event;
    }

    /**
     * @param OrmEntity $entity
     * @param string    $relationName
     * @param string    $id
     *
     * @return OrmEntity|null
     */
    protected function findForeignEntity(OrmEntity $entity, string $relationName, string $id): ?OrmEntity
    {
        $foreignEntityName = $this
            ->getContainer()
            ->get('metadata')
            ->get(['entityDefs', $entity->getEntityType(), 'links', $relationName, 'entity']);

        return (!empty($foreignEntityName)) ? $this->getEntityManager()->getEntity($foreignEntityName, $id) : null;
    }

    /**
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function dispatch(string $target, string $action, array $data): array
    {
        return $this->getContainer()->get('eventManager')->dispatch($target, $action, $data);
    }
}
