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

namespace Treo\Services;

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class MassActionProgressManagerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class MassActionProgressManagerTest extends TestCase
{
    /**
     * Test is push method exists
     */
    public function testIsPushMethodExists()
    {
        $service = $this->createMockService(MassActionProgressManager::class);

        $this->assertTrue(method_exists($service, 'push'));
    }

    /**
     * Test is executeProgressJob return true
     */
    public function testIsExecuteProgressJobReturnTrue()
    {
        $service = $this->createMockService(
            MassActionProgressManager::class,
            ['getDataFromFile', 'checkExists', 'getService']
        );

        $service
            ->expects($this->once())
            ->method('getDataFromFile')
            ->willReturn(['key' => 'value']);

        $service
            ->expects($this->once())
            ->method('checkExists')
            ->willReturn(true);

        $service
            ->expects($this->once())
            ->method('getService')
            ->willReturn(null);

        // test 1
        $this->assertTrue($service->executeProgressJob([
            'progressOffset' => 1,
            'data' => '{"fileId":1,"entityType":"Product","total":1,"action":"some-action"}'
        ]));

        $this->checkProgressJobExecution($service);

        // test 2
        $this->assertTrue($service->executeProgressJob([]));

        $this->checkProgressJobExecution($service);

        // test 3
        $this->assertTrue($service->executeProgressJob([
            'progressOffset' => 1,
            'data' => []
        ]));

        $this->checkProgressJobExecution($service);
    }

    /**
     * Test is method throw exception
     *
     * @expectedException \Espo\Core\Exceptions\Error
     */
    public function testIsExecuteProgressJobThrowException()
    {
        $service = $this->createMockService(
            MassActionProgressManager::class,
            ['getDataFromFile', 'checkExists', 'getService']
        );

        $service
            ->expects($this->once())
            ->method('getDataFromFile')
            ->willReturn([
                'key' => 'value'
            ]);

        $service
            ->expects($this->once())
            ->method('checkExists')
            ->willReturn(true);

        $service
            ->expects($this->once())
            ->method('getService')
            ->willThrowException(
                new \Espo\Core\Exceptions\Error()
            );

        // test is method throw exception when wrong entityType
        $this->assertTrue($service->executeProgressJob([
            'progressOffset' => 1,
            'data' => '{"fileId":1,"entityType":"Entity"}'
        ]));
    }

    /**
     * Check project job execution
     *
     * @param MassActionProgressManager $service
     */
    protected function checkProgressJobExecution(MassActionProgressManager $service)
    {
        // test progress job status
        $this->assertEquals('success', $service->getStatus());

        // test progress job progress
        $this->assertEquals(100, $service->getProgress());
    }
}
