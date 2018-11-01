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
     * Test is translate method exists
     */
    public function testIsTranslateMethodExists()
    {
        $service = $this->createMockService(ProgressManager::class, ['translate']);

        $this->assertTrue(method_exists($service, 'translate'));
    }

    /**
     * Test is popupData return array
     */
    public function testIsPopupDataReturnArray()
    {
        $service = $this->createMockService(ProgressManager::class, ['getMaxSize', 'getDbData', 'getProgressesList']);
        $request = $this->createMockService(Request::class);

        $service
            ->expects($this->any())
            ->method('getMaxSize')
            ->willReturn(5);

        $service
            ->expects($this->any())
            ->method('getDbData')
            ->willReturn([
                'id' => 'some-id',
                'name' => 'some-name'
            ]);

        $service
            ->expects($this->any())
            ->method('getProgressesList')
            ->willReturn([
                'total' => 0,
                'list' => []
            ]);

        $this->assertInternalType('array', $service->popupData($request));
    }

    /**
     * Test is getItemInActions return array
     */
    public function testIsGetItemActionsReturnArray()
    {
        $service = $this->createMockService(ProgressManager::class, ['getProgressConfig', 'getActions']);

        $service
            ->expects($this->any())
            ->method('getProgressConfig')
            ->willReturn([
                'statusAction' => [],
                'type' => []
            ]);

        $service
            ->expects($this->any())
            ->method('getActions')
            ->willReturn([
                [
                    'type' => 'some-type',
                    'data' => 'some-data'
                ]
            ]);

        // test 1
        $this->assertInternalType('array', $service->getItemActions('status', ['type' => '']));

        // test 2
        $this->assertInternalType('array', $service->getItemActions('status', ['type' => '', 'name' => []]));

        // test 3
        $this->assertInternalType('array', $service->getItemActions('', ['type' => '']));
    }
}
