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
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeSave', $event);

        $this->setOwnerUser($event);
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterSave', $event);
    }

    /**
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeRemove', $event);
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterRemove', $event);
    }

    /**
     * @param Event $event
     */
    public function beforeMassRelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeMassRelate', $event);
    }

    /**
     * @param Event $event
     */
    public function afterMassRelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterMassRelate', $event);
    }

    /**
     * @param Event $event
     */
    public function beforeRelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeRelate', $event);
    }

    /**
     * @param Event $event
     */
    public function afterRelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterRelate', $event);
    }

    /**
     * @param Event $event
     */
    public function beforeUnrelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'beforeUnrelate', $event);
    }

    /**
     * @param Event $event
     */
    public function afterUnrelate(Event $event)
    {
        // delegate an event
        $this->dispatch($event->getArgument('entityType') . 'Entity', 'afterUnrelate', $event);
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

    /**
     * @param Event $event
     */
    private function setOwnerUser(Event $event)
    {
        if (empty($event->getArgument('hooksDisabled')) && empty($event->getArgument('options')['skipHooks'])) {
            // get entity
            $entity = $event->getArgument('entity');

            // get metadata
            $metadata = $this->getContainer()->get('metadata');

            // has owner param
            $hasOwner = !empty($metadata->get('scopes.' . $entity->getEntityType() . '.hasOwner'));

            if ($hasOwner && empty($entity->get('ownerUserId'))) {
                $entity->set('ownerUserId', $entity->get('createdById'));
            }
        }
    }
}
