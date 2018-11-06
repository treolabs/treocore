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
use Slim\Http\Request;

/**
 * Class ProgressManagerTest
 *
 * @author r.zablodskiy@zinitsolutions.com
 */
class ProgressManagerTest extends TestCase
{
    /**
     * Test popupData method
     */
    public function testPopupDataMethod()
    {
        $service = $this->createMockService(
            ProgressManager::class,
            ['getMaxSize', 'getDbData', 'getDbDataTotal', 'translate', 'getItemActions', 'updateStatus']
        );
        $request = $this->createMockService(Request::class);

        $service
            ->expects($this->any())
            ->method('getMaxSize')
            ->willReturn(5);

        $service
            ->expects($this->any())
            ->method('getDbData')
            ->willReturn([
                [
                    'id' => 'some-id',
                    'name' => 'some-name',
                    'progress' => 0,
                    'status' => '1_new'
                ]
            ]);

        $service
            ->expects($this->any())
            ->method('getDbDataTotal')
            ->willReturn(1);

        $service
            ->expects($this->any())
            ->method('translate')
            ->willReturn('Translate');

        $service
            ->expects($this->any())
            ->method('getItemActions')
            ->willReturn([
                [
                    'type' => 'some-type',
                    'data' => []
                ]
            ]);

        $service
            ->expects($this->any())
            ->method('updateStatus')
            ->willReturn(null);

        // test 1
        $this->assertEquals(
            [
                'total' => 1,
                'list' => [
                    [
                        'id' => 'some-id',
                        'name' => 'some-name',
                        'progress' => 0,
                        'status' => [
                            'key' => 'new',
                            'translate' => 'Translate'
                        ],
                        'actions' => [
                            [
                                'type' => 'some-type',
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $service->popupData($request)
        );

        $service = $this->createMockService(ProgressManager::class, ['getMaxSize', 'getDbData']);

        $service
            ->expects($this->any())
            ->method('getMaxSize')
            ->willReturn(5);

        $service
            ->expects($this->any())
            ->method('getDbData')
            ->willReturn([]);

        // test 2
        $this->assertEquals(['total' => 0, 'list' => []], $service->popupData($request));
    }

    /**
     * Test getItemInActions method
     */
    public function testGetItemActionsMethod()
    {
        $service = $this->createMockService(
            ProgressManager::class,
            ['getProgressConfig']
        );

        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([]);

        // test 1
        $this->assertEquals([], $service->getItemActions('new', ['type' => 'some-type']));

        $service = $this->createMockService(
            ProgressManager::class,
            ['getProgressConfig', 'getService']
        );
        $customService = $this->createMockService(CancelStatusAction::class, ['getProgressStatusActionData']);

        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([
                'statusAction' => [
                    'new' => ['cancel']
                ],
                'actionService' => [
                    'cancel' => 'CancelStatusAction'
                ],
                'type' => []
            ]);

        $service
            ->expects($this->any())
            ->method('getService')
            ->willReturn($customService);

        $customService
            ->expects($this->any())
            ->method('getProgressStatusActionData')
            ->willReturn([
                'field1' => 'value1',
                'field2' => 'value2'
            ]);

        // test 2
        $this->assertEquals(
            [
                [
                    'type' => 'cancel',
                    'data' => [
                        'field1' => 'value1',
                        'field2' => 'value2'
                    ]
                ]
            ],
            $service->getItemActions('new', ['type' => 'some-type'])
        );

        // test 3
        $this->assertEquals([], $service->getItemActions('', ['type' => '']));
    }

    /**
     * Test is translate method exists
     */
    public function testIsTranslateMethodExists()
    {
        $service = $this->createMockService(ProgressManager::class, ['translate']);

        $this->assertTrue(method_exists($service, 'translate'));
    }
}
