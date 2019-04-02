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

namespace Treo\Core\Utils;

use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Module;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class ConfigTest extends TestCase
{
    /**
     * Test getModules method
     */
    public function testGetModulesMethod()
    {
        $mock = $this->createPartialMock(Config::class, ['getModuleUtil', 'getFileManager']);
        $fileManager = $this->createPartialMock(Manager::class, ['getFileList']);
        $moduleUtil = $this->createPartialMock(Module::class, ['get']);

        $moduleUtil
            ->expects($this->any())
            ->method('get')
            ->withConsecutive(['module1.order'], ['module2.order'])
            ->willReturnOnConsecutiveCalls(20, 10);

        $fileManager
            ->expects($this->any())
            ->method('getFileList')
            ->willReturn(['module1', 'module2']);

        $mock
            ->expects($this->any())
            ->method('getModuleUtil')
            ->willReturn($moduleUtil);
        $mock
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);

        // test 1
        $expects = ['module2', 'module1'];
        $this->assertEquals($expects, $mock->getModules());

        // test 2
        $mock = $this->createPartialMock(Config::class, ['getModuleUtil', 'getFileManager']);
        $fileManager = $this->createPartialMock(Manager::class, ['getFileList']);
        $moduleUtil = $this->createPartialMock(Module::class, []);

        $fileManager
            ->expects($this->any())
            ->method('getFileList')
            ->willReturn([]);

        $mock
            ->expects($this->any())
            ->method('getModuleUtil')
            ->willReturn($moduleUtil);
        $mock
            ->expects($this->any())
            ->method('getFileManager')
            ->willReturn($fileManager);

        $this->assertEquals([], $mock->getModules());

        // test 3
        $reflection = new \ReflectionClass($mock);
        $modules = $reflection->getProperty('modules');
        $modules->setAccessible(true);
        $modules->setValue($mock, ['Module']);

        $this->assertEquals(['Module'], $mock->getModules());
    }

    /**
     * Test is getDefaults method exists
     */
    public function testIsGetDefaultsExists()
    {
        $mock = $this->createPartialMock(Config::class, []);

        // test
        $this->assertTrue(method_exists($mock, 'getDefaults'));
    }
}
