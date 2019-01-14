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

namespace Treo\Listeners;

use Treo\PHPUnit\Framework\TestCase;
use Treo\Services\Composer as ComposerService;

/**
 * Class ComposerTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class ComposerTest extends TestCase
{
    /**
     * Test beforeComposerUpdate method
     */
    public function testBeforeComposerUpdateMethod()
    {
        $service = $this->createMockService(Composer::class, ['getComposerService']);
        $composerService = $this->createMockService(ComposerService::class, ['storeComposerLock']);

        $composerService
            ->expects($this->any())
            ->method('storeComposerLock')
            ->willReturn(null);

        $service
            ->expects($this->any())
            ->method('getComposerService')
            ->willReturn($composerService);

        $testData = [
            'request' => null,
            'data' => [],
            'params' => []
        ];

        // test
        $this->assertArrayKeys($testData, $service->beforeComposerUpdate($testData));
    }

    /**
     * Test afterComposerUpdate method
     */
    public function testAfterComposerUpdateMethod()
    {
        $service = $this->createMockService(Composer::class, ['notifyComposerUpdate']);
        $service
            ->expects($this->any())
            ->method('notifyComposerUpdate')
            ->willReturn(null);

        // test
        $testData = [
            'composer' => [
                'status' => 0
            ],
            'createdById' => 'id'
        ];

        $this->assertArrayKeys($testData, $service->afterComposerUpdate($testData));
    }

    /**
     * Test afterInstallModule method
     */
    public function testAfterInstallModuleMethod()
    {
        $service = $this->createMockService(Composer::class, ['notifyInstall']);

        $service
            ->expects($this->any())
            ->method('notifyInstall')
            ->willReturn(null);

        // test
        $testData = [
            'id' => 'some-id',
            'createdById' => 'some-id'
        ];

        $this->assertArrayKeys($testData, $service->afterInstallModule($testData));
    }

    /**
     * Test afterUpdateModule method
     */
    public function testAfterUpdateModuleMethod()
    {
        $service = $this->createMockService(Composer::class, ['notifyUpdate']);

        $service
            ->expects($this->any())
            ->method('notifyUpdate')
            ->willReturn(null);

        // test
        $testData = [
            'id' => 'some-id',
            'from' => '',
            'createdById' => 'some-id'
        ];

        $this->assertArrayKeys($testData, $service->afterUpdateModule($testData));
    }

    /**
     * Test afterDeleteModule method
     */
    public function testAfterDeleteModuleMethod()
    {
        $service = $this->createMockService(Composer::class, ['notifyDelete']);

        $service
            ->expects($this->any())
            ->method('notifyDelete')
            ->willReturn(null);

        // test
        $testData = [
            'id' => 'some-id',
            'createdById' => 'some-id'
        ];

        $this->assertArrayKeys($testData, $service->afterDeleteModule($testData));
    }
}
