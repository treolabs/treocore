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

declare(strict_types=1);

namespace Treo\Core\Loaders;

use PHPUnit\Framework\TestCase;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Preferences as Entity;

/**
 * Class PreferencesTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class PreferencesTest extends TestCase
{
    /**
     * Test load method
     */
    public function testLoad()
    {
        $mock = $this->createPartialMock(Preferences::class, ['getEntityManager', 'getUser']);
        $entityManager = $this->createPartialMock(EntityManager::class, ['getEntity']);
        $user = $this->createPartialMock(User::class, []);
        $preferences = $this->createPartialMock(Entity::class, []);

        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, '1');

        $entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->withConsecutive(['Preferences', '1'])
            ->willReturnOnConsecutiveCalls($preferences);
        $mock
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);
        $mock
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        // test
        $this->assertInstanceOf(Entity::class, $mock->load());
    }
}
