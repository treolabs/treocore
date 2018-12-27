<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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

namespace Treo\Core;

/**
 * Class QueueManagerTest
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class QueueManagerTest extends \Treo\PHPUnit\Framework\TestCase
{
    public function testIsRunMethodReturnTrue()
    {
        $mock = $this->createPartialMock(QueueManager::class, ['updateStatuses', 'getItemToRun', 'createCronJob']);
        $mock
            ->expects($this->any())
            ->method('updateStatuses')
            ->willReturn(null);
        $mock
            ->expects($this->any())
            ->method('getItemToRun')
            ->willReturn(null);
        $mock
            ->expects($this->any())
            ->method('createCronJob')
            ->willReturn(null);

        // test
        $this->assertTrue($mock->run());
    }

    public function testIsPushMethodReturnTrue()
    {
        // prepare methods
        $methods = [
            'isService',
            'getItemToRun',
            'createQueueItem',
            'createCronJob'
        ];

        $mock = $this->createPartialMock(QueueManager::class, $methods);
        $mock
            ->expects($this->any())
            ->method('getItemToRun')
            ->willReturn(null);
        $mock
            ->expects($this->any())
            ->method('createCronJob')
            ->willReturn(null);
        $mock
            ->expects($this->any())
            ->method('isService')
            ->willReturn(true);
        $mock
            ->expects($this->any())
            ->method('createQueueItem')
            ->willReturn(true);

        // test 1
        $this->assertTrue($mock->push('name', 'service1', ['some-data' => 1]));

        // test 2
        $this->assertTrue($mock->push('name', 'service1'));
    }

    public function testIsPushMethodReturnFalse()
    {
        // prepare methods
        $methods = [
            'isService',
            'getItemToRun',
            'createQueueItem',
            'createCronJob'
        ];

        $mock = $this->createPartialMock(QueueManager::class, $methods);
        $mock
            ->expects($this->any())
            ->method('getItemToRun')
            ->willReturn(null);
        $mock
            ->expects($this->any())
            ->method('createCronJob')
            ->willReturn(null);

        // clonning mock
        $mock1 = clone $mock;

        // test 1
        $mock->expects($this->any())->method('isService')->willReturn(false);
        $this->assertFalse($mock->push('name', 'service1', ['some-data' => 1]));
        $this->assertFalse($mock->push('name', 'service1'));

        // test 2
        $mock1->expects($this->any())->method('isService')->willReturn(true);
        $mock1->expects($this->any())->method('createQueueItem')->willReturn(false);
        $this->assertFalse($mock1->push('name', 'service1', ['some-data' => 1]));
        $this->assertFalse($mock1->push('name', 'service1'));
    }
}
