<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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

namespace Treo\Core\Acl;

use Espo\Entities\User;
use Espo\ORM\Entity;
use Treo\Core\Utils\Metadata;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class BaseTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class BaseTest extends TestCase
{
    protected $entityType = 'TestType';

    /**
     * Test checkIsOwner method return true
     */
    public function testCheckIsOwnerReturnTrue()
    {
        $user = $this->createMockService(User::class);
        $user->id = 'some-id';

        // test 1
        $metadata = $this->createMockService(Metadata::class, ['get']);
        $metadata
            ->method('get')
            ->withConsecutive(
                ['scopes.' . $this->entityType . '.hasOwnerUser']
            )->willReturnOnConsecutiveCalls(true);

        $service = $this->createMockService(Base::class, ['getInjection']);
        $service
            ->expects($this->any())
            ->method('getInjection')
            ->willReturn($metadata);

        $entity = $this->createMockService(Entity::class, ['getEntityType', 'has', 'get']);
        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($this->entityType);
        $entity
            ->method('has')
            ->withConsecutive(['ownerUserId'])
            ->willReturnOnConsecutiveCalls(true);
        $entity
            ->method('get')
            ->withConsecutive(['ownerUserId'])
            ->willReturnOnConsecutiveCalls('some-id');

        $this->assertTrue($service->checkIsOwner($user, $entity));

        // test 2
        $metadata = $this->createMockService(Metadata::class, ['get']);
        $metadata
            ->method('get')
            ->withConsecutive(
                ['scopes.' . $this->entityType . '.hasOwnerUser'],
                ['scopes.' . $this->entityType . '.hasAssignedUser']
            )->willReturnOnConsecutiveCalls(false, true);
        $service = $this->createMockService(Base::class, ['getInjection']);
        $service
            ->expects($this->any())
            ->method('getInjection')
            ->willReturn($metadata);

        $entity = $this->createMockService(Entity::class, ['getEntityType', 'has', 'get']);
        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($this->entityType);
        $entity
            ->method('has')
            ->withConsecutive(['assignedUserId'])
            ->willReturnOnConsecutiveCalls(true);
        $entity
            ->method('get')
            ->withConsecutive(['assignedUserId'])
            ->willReturnOnConsecutiveCalls('some-id');

        $this->assertTrue($service->checkIsOwner($user, $entity));

        // test 3
        $metadata = $this->createMockService(Metadata::class, ['get']);
        $metadata
            ->method('get')
            ->withConsecutive(
                ['scopes.' . $this->entityType . '.hasOwnerUser'],
                ['scopes.' . $this->entityType . '.hasAssignedUser']
            )->willReturnOnConsecutiveCalls(false, false);
        $service = $this->createMockService(Base::class, ['getInjection']);
        $service
            ->expects($this->any())
            ->method('getInjection')
            ->willReturn($metadata);

        $entity = $this->createMockService(Entity::class, ['getEntityType', 'hasAttribute', 'has', 'get']);
        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($this->entityType);
        $entity
            ->method('hasAttribute')
            ->withConsecutive(['createdById'])
            ->willReturnOnConsecutiveCalls(true);
        $entity
            ->method('has')
            ->withConsecutive(['createdById'])
            ->willReturnOnConsecutiveCalls(true);
        $entity
            ->method('get')
            ->withConsecutive(['createdById'])
            ->willReturnOnConsecutiveCalls('some-id');

        $this->assertTrue($service->checkIsOwner($user, $entity));

        // test 4
        $metadata = $this->createMockService(Metadata::class, ['get']);
        $metadata
            ->method('get')
            ->withConsecutive(
                ['scopes.' . $this->entityType . '.hasOwnerUser'],
                ['scopes.' . $this->entityType . '.hasAssignedUser']
            )->willReturnOnConsecutiveCalls(false, false);
        $service = $this->createMockService(Base::class, ['getInjection']);
        $service
            ->expects($this->any())
            ->method('getInjection')
            ->willReturn($metadata);

        $entity = $this->createMockService(
            Entity::class,
            ['getEntityType', 'hasAttribute', 'hasRelation', 'hasLinkMultipleId']
        );
        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($this->entityType);
        $entity
            ->method('hasAttribute')
            ->withConsecutive(['createdById'], ['assignedUsersIds'])
            ->willReturnOnConsecutiveCalls(false, true);
        $entity
            ->method('hasRelation')
            ->withConsecutive(['assignedUsers'])
            ->willReturnOnConsecutiveCalls(true);
        $entity
            ->method('hasLinkMultipleId')
            ->withConsecutive(['assignedUsers', 'some-id'])
            ->willReturnOnConsecutiveCalls(true);

        $this->assertTrue($service->checkIsOwner($user, $entity));
    }

    public function testCheckIsOwnerReturnFalse()
    {
        $user = $this->createMockService(User::class);

        $metadata = $this->createMockService(Metadata::class, ['get']);
        $metadata
            ->method('get')
            ->withConsecutive(
                ['scopes.' . $this->entityType . '.hasOwnerUser'],
                ['scopes.' . $this->entityType . '.hasAssignedUser']
            )->willReturnOnConsecutiveCalls(false, false);
        $service = $this->createMockService(Base::class, ['getInjection']);
        $service
            ->expects($this->any())
            ->method('getInjection')
            ->willReturn($metadata);

        $entity = $this->createMockService(
            Entity::class,
            ['getEntityType', 'hasAttribute', 'hasRelation', 'hasLinkMultipleId']
        );
        $entity
            ->expects($this->any())
            ->method('getEntityType')
            ->willReturn($this->entityType);
        $entity
            ->method('hasAttribute')
            ->withConsecutive(['createdById'], ['assignedUsersIds'])
            ->willReturnOnConsecutiveCalls(false, false);

        // test
        $this->assertFalse($service->checkIsOwner($user, $entity));
    }
}
