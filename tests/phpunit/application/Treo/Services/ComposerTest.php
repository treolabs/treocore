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

namespace Treo\Services;

use Treo\PHPUnit\Framework\TestCase;

/**
 * Class ComposerTest
 *
 * @author r.ratsun@zinitsolutions.com
 */
class ComposerTest extends TestCase
{
    public function testIsCreateUpdateJobReturnFalse()
    {
        $service = $this->createMockService(Composer::class, ['insertJob', 'isSystemUpdating']);
        $service
            ->expects($this->any())
            ->method('insertJob')
            ->willReturn(null);
        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(true);

        // test
        $this->assertFalse($service->createUpdateJob());
    }

    public function testIsCreateUpdateJobReturnTrue()
    {
        $service = $this->createMockService(Composer::class, ['insertJob', 'isSystemUpdating']);
        $service
            ->expects($this->any())
            ->method('insertJob')
            ->willReturn(null);
        $service
            ->expects($this->any())
            ->method('isSystemUpdating')
            ->willReturn(false);

        // test
        $this->assertTrue($service->createUpdateJob());
    }

    public function testIsRunUpdateJobReturnTrue()
    {
        $service = $this->createMockService(Composer::class, ['runUpdate']);
        $service
            ->expects($this->any())
            ->method('runUpdate')
            ->willReturn([]);

        // tests
        $this->assertTrue($service->runUpdateJob(['createdById' => '1']));
        $this->assertTrue($service->runUpdateJob(['createdById' => '2']));
        $this->assertTrue($service->runUpdateJob(['createdById' => '2', 'qwe' => 123]));
    }

    public function testIsRunUpdateMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'runUpdate'));
    }

    public function testIsCancelChangesMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'cancelChanges'));
    }

    public function testIsUpdateMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'update'));
    }

    public function testIsDeleteMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'delete'));
    }

    public function testIsGetModuleComposerJsonMethodReturnArray()
    {
        $service = $this->createMockService(Composer::class, ['setModuleComposerJson']);
        $service
            ->expects($this->any())
            ->method('setModuleComposerJson')
            ->willReturn(null);

        $result = $service->getModuleComposerJson();

        // test 1
        $this->assertInternalType('array', $result);

        // test 2
        $this->assertTrue(isset($result['require']));
    }

    public function testIsGetModuleStableComposerJsonReturnArray()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertInternalType('array', $service->getModuleStableComposerJson());
    }

    public function testIsSetModuleComposerJsonMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'setModuleComposerJson'));
    }

    public function testIsSaveComposerJsonMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'saveComposerJson'));
    }

    public function testIsStoreComposerLockMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'storeComposerLock'));
    }

    public function testIsGetComposerLockDiffMethodReturnArray()
    {
        $service = $this->createMockService(Composer::class);

        $result = $service->getComposerLockDiff();

        // test 1
        $this->assertInternalType('array', $result);

        // test 2
        $this->assertTrue(isset($result['install']));

        // test 3
        $this->assertTrue(isset($result['update']));

        // test 4
        $this->assertTrue(isset($result['delete']));
    }

    public function testIsGetComposerDiffMethodReturnArray()
    {
        $service = $this->createMockService(Composer::class, ['getModuleId', 'getModule']);
        $service
            ->expects($this->any())
            ->method('getModuleId')
            ->willReturn('moduleId');
        $service
            ->expects($this->any())
            ->method('getModule')
            ->willReturn(['version' => 'some-version']);

        $result = $service->getComposerDiff();

        // test 1
        $this->assertInternalType('array', $result);

        // test 2
        $this->assertTrue(isset($result['install']));

        // test 3
        $this->assertTrue(isset($result['update']));

        // test 4
        $this->assertTrue(isset($result['delete']));
    }

    public function testIsUpdateMinimumStabilityMethodReturnTrue()
    {
        $service = $this->createMockService(Composer::class, ['filePutContents']);
        $service
            ->expects($this->any())
            ->method('filePutContents')
            ->willReturn(1);

        // test
        $this->assertTrue($service->updateMinimumStability());
    }

    public function testIsSystemUpdatingMethodExists()
    {
        $service = $this->createMockService(Composer::class);

        // test
        $this->assertTrue(method_exists($service, 'isSystemUpdating'));
    }
}
