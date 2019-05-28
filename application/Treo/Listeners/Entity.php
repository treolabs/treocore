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
use Treo\Core\EventManager\Event;

/**
 * Class Entity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Entity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeSave(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeSave', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process(
                    $event->getArgument('entityType'),
                    'beforeSave',
                    $event->getArgument('entity'),
                    $event->getArgument('options')
                );
        }
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterSave', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process(
                    $event->getArgument('entityType'),
                    'afterSave',
                    $event->getArgument('entity'),
                    $event->getArgument('options')
                );
        }
    }

    /**
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeRemove', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process(
                    $event->getArgument('entityType'),
                    'beforeRemove',
                    $event->getArgument('entity'),
                    $event->getArgument('options')
                );
        }
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterRemove', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process(
                    $event->getArgument('entityType'),
                    'afterRemove',
                    $event->getArgument('entity'),
                    $event->getArgument('options')
                );
        }
    }

    /**
     * @param Event $event
     */
    public function beforeMassRelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeMassRelate', $event);
    }

    /**
     * @param Event $event
     */
    public function afterMassRelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterMassRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $hookData = [
                'relationName'   => $event->getArgument('relationName'),
                'relationParams' => $event->getArgument('relationParams')
            ];
            $this
                ->getEntityManager()
                ->getHookManager()
                ->process(
                    $event->getArgument('entityType'),
                    'afterMassRelate',
                    $event->getArgument('entity'),
                    $event->getArgument('options'),
                    $hookData
                );
        }
    }

    /**
     * @param Event $event
     */
    public function beforeRelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled')) && empty($event->getArgument('options')['skipHooks'])) {
            $foreign = $event->getArgument('foreign');
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity(
                    $event->getArgument('entity'),
                    $event->getArgument('relationName'),
                    (string)$foreign
                );
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event->getArgument('relationName'),
                    'relationData'  => $event->getArgument('relationData'),
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process(
                        $event->getArgument('entityType'),
                        'beforeRelate',
                        $event->getArgument('entity'),
                        $event->getArgument('options'),
                        $hookData
                    );
            }
        }
    }

    /**
     * @param Event $event
     */
    public function afterRelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterRelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled')) && empty($event->getArgument('options')['skipHooks'])) {
            $foreign = $event->getArgument('foreign');
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity(
                    $event->getArgument('entity'),
                    $event->getArgument('relationName'),
                    (string)$foreign
                );
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event->getArgument('relationName'),
                    'relationData'  => $event->getArgument('relationData'),
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process(
                        $event->getArgument('entityType'),
                        'afterRelate',
                        $event->getArgument('entity'),
                        $event->getArgument('options'),
                        $hookData
                    );
            }
        }
    }

    /**
     * @param Event $event
     */
    public function beforeUnrelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeUnrelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $foreign = $event->getArgument('foreign');
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity(
                    $event->getArgument('entity'),
                    $event->getArgument('relationName'),
                    (string)$foreign
                );
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event->getArgument('relationName'),
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process(
                        $event->getArgument('entityType'),
                        'beforeUnrelate',
                        $event->getArgument('entity'),
                        $event->getArgument('options'),
                        $hookData
                    );
            }
        }
    }

    /**
     * @param Event $event
     */
    public function afterUnrelate(Event $event)
    {
        // dispatch an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterUnrelate', $event);

        /**
         * @deprecated it will be removed soon
         */
        if (empty($event->getArgument('hooksDisabled'))
            && empty($event->getArgument('options')['skipHooks'])) {
            $foreign = $event->getArgument('foreign');
            if ($foreign instanceof OrmEntity) {
                $foreign = $this->findForeignEntity(
                    $event->getArgument('entity'),
                    $event->getArgument('relationName'),
                    (string)$foreign
                );
            }

            if ($foreign instanceof OrmEntity) {
                $hookData = [
                    'relationName'  => $event->getArgument('relationName'),
                    'foreignEntity' => $foreign
                ];
                $this
                    ->getEntityManager()
                    ->getHookManager()
                    ->process(
                        $event->getArgument('entityType'),
                        'afterUnrelate',
                        $event->getArgument('entity'),
                        $event->getArgument('options'),
                        $hookData
                    );
            }
        }
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
     * @param Event  $event
     */
    protected function dispatch(string $target, string $action, Event $event)
    {
        $this->getContainer()->get('eventManager')->dispatch($target, $action, $event);
    }
}
