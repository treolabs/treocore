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

namespace Treo\Core;

use Treo\Core\Utils\Metadata;
use Treo\PHPUnit\Framework\TestCase;
use Espo\Entities\User;

/**
 * Class ProgressManagerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class ProgressManagerTest extends TestCase
{
    /**
     * Test push method return true
     */
    public function testPushReturnTrue()
    {
        $service = $this->createMockService(ProgressManager::class, ['getProgressConfig', 'insert', 'getUser']);
        $user = $this->createMockService(User::class, ['get']);

        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([
                'actionService' => [],
                'statusAction'  => [],
                'type'          => [
                    'massAction' => []
                ]
            ]);
        $service
            ->expects($this->any())
            ->method('insert')
            ->willReturn(true);
        $service
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $user
            ->expects($this->any())
            ->method('get')
            ->willReturn('some-id');

        // test 1
        $this->assertTrue($service->push('some-name', 'massAction', [], '1'));

        // test 2
        $this->assertTrue($service->push('some-name', 'massAction'));
    }

    /**
     * Test push method return false
     */
    public function testPushReturnFalse()
    {
        $service = $this->createMockService(ProgressManager::class, ['getProgressConfig', 'insert']);
        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([
                'actionService' => [],
                'statusAction'  => [],
                'type'          => [
                    'massAction' => []
                ]
            ]);

        // test 1
        $this->assertFalse($service->push('some-name', 'someAction', [], '1'));

        $service = $this->createMockService(ProgressManager::class, ['getProgressConfig', 'insert']);
        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([
                'actionService' => [],
                'statusAction'  => [],
                'type'          => [
                    'massAction' => []
                ]
            ]);
        $service
            ->expects($this->any())
            ->method('insert')
            ->willReturn(false);

        // test 2
        $this->assertFalse($service->push('some-name', 'massAction', [], '1'));
    }

    /**
     * Test getPopupData method
     */
    public function testGetPopupDataMethod()
    {
        // prepare methods
        $methods = ['fileExists', 'fileGetContents', 'cronIsNotRunning'];

        $service = $this->createMockService(ProgressManager::class, $methods);

        $service
            ->expects($this->any())
            ->method('fileExists')
            ->willReturn(false);
        $service
            ->expects($this->any())
            ->method('cronIsNotRunning')
            ->willReturn(false);

        // test 1
        $this->assertEquals([], $service->getPopupData());

        $service = $this->createMockService(ProgressManager::class, $methods);
        $service
            ->expects($this->any())
            ->method('fileExists')
            ->willReturn(true);
        $service
            ->expects($this->any())
            ->method('fileGetContents')
            ->willReturn('[]');
        $service
            ->expects($this->any())
            ->method('cronIsNotRunning')
            ->willReturn(false);

        // test 2
        $this->assertEquals([], $service->getPopupData());

        $service = $this->createMockService(ProgressManager::class, $methods);
        $service
            ->expects($this->any())
            ->method('fileExists')
            ->willReturn(true);
        $service
            ->expects($this->any())
            ->method('fileGetContents')
            ->willReturn('["id1","id2"]');
        $service
            ->expects($this->any())
            ->method('cronIsNotRunning')
            ->willReturn(false);

        // test 3
        $expects = [
            'id1',
            'id2'
        ];
        $this->assertEquals($expects, $service->getPopupData());

        $service = $this->createMockService(ProgressManager::class, $methods);
        $service
            ->expects($this->any())
            ->method('fileExists')
            ->willReturn(true);
        $service
            ->expects($this->any())
            ->method('fileGetContents')
            ->willReturn('"field":"value"');
        $service
            ->expects($this->any())
            ->method('cronIsNotRunning')
            ->willReturn(false);

        // test 4
        $expects = [];
        $this->assertEquals($expects, $service->getPopupData());
    }

    /**
     * Test hidePopup exists
     */
    public function testHidePopupMethodExists()
    {
        $service = $this->createMockService(ProgressManager::class);

        // test
        $this->assertTrue(method_exists($service, 'hidePopup'));
    }

    /**
     * Test run method exists
     */
    public function testRunMethodExists()
    {
        $service = $this->createMockService(ProgressManager::class);

        // test
        $this->assertTrue(method_exists($service, 'run'));
    }

    /**
     * Test getProgressConfig method
     */
    public function testGetProgressConfig()
    {
        $service = $this->createMockService(ProgressManager::class, ['getMetadata', 'includeFile']);
        $metadata = $this->createMockService(Metadata::class, ['getModuleList']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('includeFile')
            ->willReturn([
                'actionService' => [],
                'statusAction'  => [],
                'type'          => []
            ]);

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn([]);




        // test 1
        $expects = [
            'actionService' => [],
            'statusAction'  => [],
            'type'          => []
        ];
        $this->assertEquals($expects, $service->getProgressConfig());

        $service = $this->createMockService(ProgressManager::class, ['getMetadata', 'includeFile']);
        $metadata = $this->createMockService(Metadata::class, ['getModuleList']);

        $service
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);
        $service
            ->expects($this->any())
            ->method('includeFile')
            ->withConsecutive(
                ['application/Treo/Configs/ProgressManager.php'],
                ['application/Espo/Modules/module1/Configs/ProgressManager.php'],
                ['application/Espo/Modules/module2/Configs/ProgressManager.php']
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'actionService' => [],
                    'statusAction'  => [],
                    'type'          => []
                ],
                [
                    'actionService' => [
                        'some-service1' => 'some-action1'
                    ],
                    'statusAction'  => [
                        'some-action1' => 'some-status1'
                    ],
                    'type'          => [
                        'some-type1' => 'some-value1'
                    ]
                ],
                [
                    'actionService' => [
                        'some-service2' => 'some-action2'
                    ],
                    'statusAction'  => [
                        'some-action2' => 'some-status2'
                    ],
                    'type'          => [
                        'some-type2' => 'some-value2'
                    ]
                ]
            );

        $metadata
            ->expects($this->any())
            ->method('getModuleList')
            ->willReturn([
                'module1',
                'module2'
            ]);

        // test 2
        $expects = [
            'actionService' => [
                'some-service1' => 'some-action1',
                'some-service2' => 'some-action2'
            ],
            'statusAction'  => [
                'some-action1' => 'some-status1',
                'some-action2' => 'some-status2'
            ],
            'type'          => [
                'some-type1' => 'some-value1',
                'some-type2' => 'some-value2'
            ]
        ];
        $this->assertEquals($expects, $service->getProgressConfig());

        $service = $this->createMockService(ProgressManager::class);

        $reflection = new \ReflectionClass($service);
        $progressConfig = $reflection->getProperty('progressConfig');
        $progressConfig->setAccessible(true);
        $progressConfig->setValue($service, [
            'actionService' => [],
            'statusAction'  => [],
            'type'          => []
        ]);

        // test 3
        $expects = [
            'actionService' => [],
            'statusAction'  => [],
            'type'          => []
        ];
        $this->assertEquals($expects, $service->getProgressConfig());
    }
}
